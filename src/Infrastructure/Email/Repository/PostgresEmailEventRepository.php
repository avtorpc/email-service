<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Repository;

use App\Application\Email\Contract\EmailCommandMessageInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final class PostgresEmailEventRepository
{
    public function __construct(
        private Connection $connection
    ) {}

    /** @return list<string> */
    public function findPendingEventIds(int $limit, int $staleAfterSeconds): array
    {
        return $this->connection->fetchFirstColumn(
            "
            SELECT event_id
            FROM email.email_processing_log
            WHERE status = 'queued'
               OR (status = 'processing'
                   AND COALESCE(updated_at, created_at) < NOW() - (:stale_seconds * INTERVAL '1 second'))
            ORDER BY created_at ASC
            LIMIT :limit
            ",
            ['stale_seconds' => $staleAfterSeconds, 'limit' => $limit],
            ['stale_seconds' => Types::INTEGER, 'limit' => Types::INTEGER]
        );
    }

    public function tryLockEvent(string $eventId): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT CASE WHEN pg_try_advisory_lock(hashtextextended(:event_id, 0)) THEN 1 ELSE 0 END',
            ['event_id' => $eventId]
        ) === 1;
    }

    public function unlockEvent(string $eventId): void
    {
        $this->connection->fetchOne(
            'SELECT pg_advisory_unlock(hashtextextended(:event_id, 0))',
            ['event_id' => $eventId]
        );
    }

    /**
     * true  -> новое событие
     * false -> duplicate delivery
     */
    public function insertRawAndProcessing(
        EmailCommandMessageInterface $command
    ): bool {
        $sql = <<<SQL
                WITH inserted_processing AS (
                
                    INSERT INTO email.email_processing_log
                    (
                        event_id,
                        event_type,
                        event_version,
                        occurred_at,
                
                        trace_id,
                        correlation_id,
                
                        producer_service,
                
                        callback,
                
                        recipient_email,
                        recipient_name,
                
                        template,
                        locale,
                
                        payload,
                
                        status,
                
                        created_at
                    )
                    VALUES
                    (
                        :event_id,
                        :event_type,
                        :event_version,
                        :occurred_at,
                
                        :trace_id,
                        :correlation_id,
                
                        :producer_service,
                
                        :callback,
                
                        :recipient_email,
                        :recipient_name,
                
                        :template,
                        :locale,
                
                        CAST(:payload AS JSONB),
                
                        'queued',
                
                        NOW()
                    )
                
                    ON CONFLICT (event_id)
                    DO NOTHING
                
                    RETURNING event_id
                )
                
                INSERT INTO email.raw_event_log
                (
                    event_id,
                    event_type,
                    event_version,
                    occurred_at,
                
                    trace_id,
                    correlation_id,
                
                    producer_service,
                
                    callback,
                
                    recipient_email,
                    recipient_name,
                
                    template,
                    locale,
                
                    payload,
                
                    duplicate,
                
                    created_at
                )
                VALUES
                (
                    :event_id,
                    :event_type,
                    :event_version,
                    :occurred_at,
                
                    :trace_id,
                    :correlation_id,
                
                    :producer_service,
                
                    :callback,
                
                    :recipient_email,
                    :recipient_name,
                
                    :template,
                    :locale,
                
                    CAST(:payload AS JSONB),
                
                    NOT EXISTS (
                        SELECT 1
                        FROM inserted_processing
                    ),
                
                    NOW()
                )
                
                RETURNING NOT EXISTS (
                    SELECT 1
                    FROM inserted_processing
                ) AS duplicate
                SQL;
        $duplicate = $this->connection->fetchOne(
            $sql,
            [
                'event_id' => $command->eventId,
                'event_type' => $command->eventType,
                'event_version' => $command->eventVersion,
                'occurred_at' => $command->occurredAt,
                'trace_id' => $command->traceId,
                'correlation_id' => $command->correlationId,
                'producer_service' => $command->producerService,
                'callback' => $command->callback,
                'recipient_email' => $command->toEmail,
                'recipient_name' => $command->toName,
                'template' => $command->template,
                'locale' => $command->templateData['locale'] ?? null,
                'payload' => array_merge(
                    ['_email_subject' => $command->subject],
                    $command->templateData
                ),
            ],
            [
                'event_version' => Types::INTEGER,
                'occurred_at' => Types::DATETIME_IMMUTABLE,
                'payload' => Types::JSON,
            ]
        );

        return $duplicate;
    }

    /**
     * =====================================================
     * LOAD EVENT
     * =====================================================
     */
    public function findByEventId(
        string $eventId
    ): ?array {

        $sql = <<<SQL
            SELECT
                event_id,
                event_type,
                event_version,
                occurred_at,
                trace_id,
                correlation_id,
                producer_service,
                callback,
                recipient_email,
                recipient_name,
                template,
                locale,
                payload,
                status,
                attempt_count,
                last_error
            FROM email.email_processing_log
            WHERE event_id = :event_id
            LIMIT 1
            SQL;

        $result = $this->connection->fetchAssociative(
            $sql,
            [
                'event_id' => $eventId,
            ]
        );

        if (!$result) {
            return null;
        }

        $payload = json_decode(
            $result['payload'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (is_string($payload)) {
            $payload = json_decode(
                $payload,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        }

        $result['payload'] = is_array($payload) ? $payload : [];

        return $result;
    }

    /**
     * =====================================================
     * STATUS → PROCESSING
     * =====================================================
     */
    public function markProcessing(
        string $eventId
    ): void {

        $sql = <<<SQL
            UPDATE email.email_processing_log
            SET
                status = 'processing',
                attempt_count = attempt_count + 1,
                last_error = NULL,
                updated_at = NOW()
            WHERE event_id = :event_id
            SQL;

        $this->connection->executeStatement(
            $sql,
            [
                'event_id' => $eventId,
            ]
        );
    }

    /**
     * =====================================================
     * STATUS → SENT
     * =====================================================
     */
    public function markSent(
        string $eventId
    ): void {

        $sql = <<<SQL
            UPDATE email.email_processing_log
            SET
                status = 'sent',
                sent_at = NOW(),
                updated_at = NOW()
            WHERE event_id = :event_id
            SQL;

        $this->connection->executeStatement(
            $sql,
            [
                'event_id' => $eventId,
            ]
        );
    }

    /**
     * =====================================================
     * STATUS → FAILED
     * =====================================================
     */
    public function markFailed(
        string $eventId,
        string $error
    ): void {

        $sql = <<<SQL
            UPDATE email.email_processing_log
            SET
                status = 'failed',
                failed_at = NOW(),
                updated_at = NOW(),
                last_error = :last_error
            WHERE event_id = :event_id
            SQL;

        $this->connection->executeStatement(
            $sql,
            [
                'event_id' => $eventId,
                'last_error' => mb_substr($error, 0, 5000),
            ]
        );
    }

    public function recordAttemptFailure(string $eventId, string $error): void
    {
        $this->connection->executeStatement(
            "
            UPDATE email.email_processing_log
            SET last_error = :last_error, updated_at = NOW()
            WHERE event_id = :event_id
            ",
            ['event_id' => $eventId, 'last_error' => mb_substr($error, 0, 5000)]
        );
    }
}
