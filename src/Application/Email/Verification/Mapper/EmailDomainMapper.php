<?php

namespace App\Application\Email\Verification\Mapper;

use App\Application\Email\Verification\DTO\VerificationEmailRawRequest;
use App\Application\Email\Verification\DTO\VerificationEmailRequest;
use DateTimeImmutable;

final class EmailDomainMapper
{
    public static function map(
        VerificationEmailRawRequest $raw
    ): VerificationEmailRequest {

        return new VerificationEmailRequest(
            eventId: self::string($raw->eventId, 'event_id'),

            eventType: self::string($raw->eventType, 'event_type'),

            eventVersion: self::int(
                $raw->eventVersion,
                'event_version'
            ),

            occurredAt: self::dateTime(
                $raw->occurredAt,
                'occurred_at'
            ),

            traceId: self::string(
                $raw->traceId,
                'trace_id'
            ),

            correlationId: self::string(
                $raw->correlationId,
                'correlation_id'
            ),

            producerService: self::string(
                $raw->producerService,
                'producer.service'
            ),

            callback: self::nullableString(
                $raw->callback
            ),

            requestId: self::string(
                $raw->requestId,
                'payload.request_id'
            ),

            template: self::string(
                $raw->template,
                'payload.template'
            ),

            locale: self::string(
                $raw->locale,
                'payload.locale'
            ),

            verificationCode: self::string(
                $raw->verificationCode,
                'payload.verification_code'
            ),

            recipientEmail: self::email(
                $raw->recipientEmail,
                'payload.recipient.email'
            ),

            recipientFirstName: self::string(
                $raw->recipientFirstName,
                'payload.recipient.first_name'
            ),

            recipientLastName: self::string(
                $raw->recipientLastName,
                'payload.recipient.last_name'
            ),

            expiresInSeconds: self::int(
                $raw->expiresInSeconds,
                'payload.expires_in_seconds'
            ),

            resendAttemptsLeft: self::int(
                $raw->resendAttemptsLeft,
                'payload.resend_attempts_left'
            ),

            payload: $raw->payload,
        );
    }

    private static function string(mixed $value, string $field): string
    {
        if ($value === null) {
            return '';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (!is_string($value)) {
            throw new \InvalidArgumentException(
                "{$field} must be string-compatible scalar"
            );
        }

        return trim($value);
    }

    private static function nullableString(
        mixed $value,
    ): string {
        if ($value === null) {
            return '';
        }

        return trim((string)$value);
    }

    private static function int(
        mixed $value,
        string $field,
    ): int {
        if (!is_int($value)) {
            throw new \InvalidArgumentException(
                "{$field} must be integer"
            );
        }

        return $value;
    }

    private static function email(
        mixed $value,
        string $field,
    ): string {

        $email = self::string($value, $field);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException(
                "{$field} invalid email"
            );
        }

        return $email;
    }

    private static function dateTime(
        mixed $value,
        string $field,
    ): DateTimeImmutable {

        $dateString = self::string($value, $field);

        try {
            return new \DateTimeImmutable($dateString);
        } catch (\Exception) {
            throw new \InvalidArgumentException(
                "{$field} must be valid ISO-8601 datetime"
            );
        }
    }
}
