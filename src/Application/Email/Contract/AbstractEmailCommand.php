<?php

namespace App\Application\Email\Contract;

abstract class AbstractEmailCommand
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly int $eventVersion,
        public readonly DateTimeImmutable $occurredAt,
        public readonly DateTimeImmutable $expiresAt,

        public readonly ?string $traceId,
        public readonly ?string $correlationId,

        public readonly ?string $producerService,
        public readonly ?string $callback,

        public readonly string $toEmail,
        public readonly string $toName,

        public readonly int $expiresInSeconds,

        public readonly string $template,
        public readonly string $subject,

        /** @var array<string,mixed> */
        public readonly array $templateData,
    ) {}
}
