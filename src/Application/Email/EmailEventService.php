<?php

namespace App\Application\Email;

use App\Application\Email\Contract\EmailCommandMessageInterface;
use App\Application\Email\Verification\Command\VerificationMailCommand;
use App\Application\Email\Verification\Validator\SendEmailCommandValidator;
use App\Infrastructure\Email\Repository\PostgresEmailEventRepository;

final class EmailEventService
{
    public function __construct(
        private SendEmailCommandValidator $validator,
        private PostgresEmailEventRepository $repository,
    ) {}

    public function ingest(EmailCommandMessageInterface $command): bool
    {
        $errors = $this->validator->validate($command);

        if ($errors !== []) {
            throw new \InvalidArgumentException(implode(', ', $errors));
        }

        return $this->repository->insertRawAndProcessing($command);
    }

    /** @return list<string> */
    public function findPendingEventIds(int $limit, int $staleAfterSeconds): array
    {
        return $this->repository->findPendingEventIds($limit, $staleAfterSeconds);
    }

    public function tryLockEvent(string $eventId): bool
    {
        return $this->repository->tryLockEvent($eventId);
    }

    public function unlockEvent(string $eventId): void
    {
        $this->repository->unlockEvent($eventId);
    }

    /**
     * =====================================================
     * LOAD EVENT
     * =====================================================
     */
    public function findByEventId(
        string $eventId
    ): ?array {

        return $this->repository->findByEventId(
            $eventId
        );
    }

    /**
     * =====================================================
     * STATUS → PROCESSING
     * =====================================================
     */
    public function markProcessing(
        string $eventId
    ): void {

        $this->repository->markProcessing(
            $eventId
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

        $this->repository->markSent(
            $eventId
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

        $this->repository->markFailed(
            $eventId,
            $error
        );
    }

    public function recordAttemptFailure(string $eventId, string $error): void
    {
        $this->repository->recordAttemptFailure($eventId, $error);
    }

}
