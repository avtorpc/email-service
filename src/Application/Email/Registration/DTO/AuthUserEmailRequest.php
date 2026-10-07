<?php

declare(strict_types=1);

namespace App\Application\Email\Registration\DTO;

use DateTimeImmutable;

final class AuthUserEmailRequest
{
    public function __construct(
        /**
         * ENVELOPE
         */
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly int $eventVersion,
        public readonly DateTimeImmutable $occurredAt,

        /**
         * TRACE
         */
        public readonly ?string $traceId,
        public readonly ?string $correlationId,

        /**
         * PRODUCER
         */
        public readonly ?string $producerService,
        public readonly ?string $callback,

        /**
         * USER DATA
         */
        public readonly string $userUuid,
        public readonly string $email,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $patronymic,
        public readonly string $phoneNumber,
        public readonly string $verificationChannelId,
        public readonly string $createdAt,
        public readonly string $updatedAt,

        /**
         * TEMPLATE (IMPORTANT FIX)
         */
        public readonly ?string $template,

        /**
         * RAW
         */
        public readonly array $payload,
    ) {}
}
