<?php

namespace App\Infrastructure\Email\Verification\Repository;

use App\Application\Email\Command\MailCommand;
use Doctrine\DBAL\Connection;

final class PostgresVerificationEmailRepository
{
    public function __construct(
        private Connection $connection,
    ) {}

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
                recipient_email,
                recipient_name,
                template,
                locale,
                payload,
                status,
                attempt_count
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

        $result['payload'] = json_decode(
            $result['payload'],
            true,
            512,
            JSON_THROW_ON_ERROR
        );

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
}
