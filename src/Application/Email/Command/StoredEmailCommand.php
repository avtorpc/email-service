<?php

declare(strict_types=1);

namespace App\Application\Email\Command;

use App\Application\Email\Contract\EmailCommandMessageInterface;
use DateTimeImmutable;

final class StoredEmailCommand implements EmailCommandMessageInterface
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $eventType,
        public readonly int $eventVersion,
        public readonly DateTimeImmutable $occurredAt,
        public readonly ?string $traceId,
        public readonly ?string $correlationId,
        public readonly ?string $producerService,
        public readonly ?string $callback,
        public readonly string $toEmail,
        public readonly string $toName,
        public readonly string $template,
        public readonly string $subject,
        public readonly array $templateData,
    ) {}

    public static function fromProcessingRow(array $row): self
    {
        $payload = $row['payload'];
        $subject = (string)($payload['_email_subject'] ?? self::fallbackSubject((string)$row['template']));
        unset($payload['_email_subject']);

        return new self(
            eventId: (string)$row['event_id'],
            eventType: (string)$row['event_type'],
            eventVersion: (int)$row['event_version'],
            occurredAt: self::toDateTimeImmutable($row['occurred_at']),
            traceId: $row['trace_id'] !== null ? (string)$row['trace_id'] : null,
            correlationId: $row['correlation_id'] !== null ? (string)$row['correlation_id'] : null,
            producerService: $row['producer_service'] !== null ? (string)$row['producer_service'] : null,
            callback: $row['callback'] !== null ? (string)$row['callback'] : null,
            toEmail: (string)$row['recipient_email'],
            toName: (string)$row['recipient_name'],
            template: (string)$row['template'],
            subject: $subject,
            templateData: $payload,
        );
    }

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

    private static function fallbackSubject(string $template): string
    {
        return match ($template) {
            'registration_verification', 'verification_code' => 'Email verification code',
            'email.send.registration_completed' => 'Регистрация успешно завершена',
            default => 'ONMI notification',
        };
    }

    private static function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable((string)$value);
    }
}
