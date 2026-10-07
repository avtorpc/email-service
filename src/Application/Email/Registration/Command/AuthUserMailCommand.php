<?php

declare(strict_types=1);

namespace App\Application\Email\Registration\Command;

use App\Application\Email\Contract\EmailCommandMessageInterface;
use DateTimeImmutable;

final class AuthUserMailCommand implements EmailCommandMessageInterface
{
    public function __construct(
        /**
         * EVENT METADATA
         */
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly int $eventVersion,
        public readonly DateTimeImmutable $occurredAt,

        /**
         * DISTRIBUTED TRACING
         */
        public readonly ?string $traceId,
        public readonly ?string $correlationId,

        /**
         * EVENT SOURCE
         */
        public readonly ?string $producerService,
        public readonly ?string $callback,

        /**
         * EMAIL DATA
         */
        public readonly string $toEmail,
        public readonly string $toName,

        /**
         * TEMPLATE
         */
        public readonly string $template,
        public readonly string $subject,

        /**
         * TEMPLATE PAYLOAD
         */
        public readonly array $templateData,
    ) {}

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getEmail(): string
    {
        return $this->toEmail;
    }

    public function getName(): string
    {
        return $this->toName;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getTemplateData(): array
    {
        return $this->templateData;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return null;
    }
}
