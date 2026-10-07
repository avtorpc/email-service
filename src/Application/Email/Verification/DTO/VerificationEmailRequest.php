<?php

namespace App\Application\Email\Verification\DTO;

use DateTimeImmutable;

final class VerificationEmailRequest
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly int $eventVersion,
        public readonly DateTimeImmutable $occurredAt,
        public readonly string $traceId,
        public readonly string $correlationId,
        public readonly string $producerService,
        public readonly string $callback,

        public readonly string $requestId,

        public readonly string $template,
        public readonly string $locale,

        public readonly string $verificationCode,

        public readonly string $recipientEmail,
        public readonly string $recipientFirstName,
        public readonly string $recipientLastName,

        public readonly int $expiresInSeconds,
        public readonly int $resendAttemptsLeft,

        public readonly array $payload,
    ) {}
}
