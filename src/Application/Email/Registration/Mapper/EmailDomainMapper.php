<?php

declare(strict_types=1);

namespace App\Application\Email\Registration\Mapper;

use App\Application\Email\Registration\DTO\AuthUserEmailRawRequest;
use App\Application\Email\Registration\DTO\AuthUserEmailRequest;
use DateTimeImmutable;

final class EmailDomainMapper
{
    public static function map(
        AuthUserEmailRawRequest $raw
    ): AuthUserEmailRequest {

        return new AuthUserEmailRequest(

        /**
         * =====================================================
         * EVENT METADATA
         * =====================================================
         */
            eventId: self::string(
                $raw->eventId,
                'event_id'
            ),

            eventType: self::string(
                $raw->eventType,
                'event_type'
            ),

            eventVersion: self::int(
                $raw->eventVersion,
                'event_version'
            ),

            occurredAt: self::dateTime(
                $raw->occurredAt,
                'occurred_at'
            ),

            /**
             * =====================================================
             * DISTRIBUTED TRACING
             * =====================================================
             */
            traceId: self::nullableString(
                $raw->traceId
            ),

            correlationId: self::nullableString(
                $raw->correlationId
            ),

            /**
             * =====================================================
             * EVENT SOURCE
             * =====================================================
             */
            producerService: self::nullableString(
                $raw->producerService
            ),

            callback: self::nullableString(
                $raw->callback
            ),

            /**
             * =====================================================
             * USER DATA
             * =====================================================
             */
            userUuid: self::string(
                $raw->userUuid,
                'payload.user_uuid'
            ),

            email: self::email(
                $raw->email,
                'payload.email'
            ),

            firstName: self::string(
                $raw->firstName,
                'payload.first_name'
            ),

            lastName: self::string(
                $raw->lastName,
                'payload.last_name'
            ),

            patronymic: self::nullableString(
                $raw->patronymic
            ),

            phoneNumber: self::string(
                $raw->phoneNumber,
                'payload.phone_number'
            ),

            verificationChannelId: self::string(
                $raw->verificationChannelId,
                'payload.verification_channel_id'
            ),

            createdAt: self::string(
                $raw->createdAt,
                'payload.created_at'
            ),

            updatedAt: self::string(
                $raw->updatedAt,
                'payload.updated_at'
            ),

            /**
             * =====================================================
             * TEMPLATE (NEW FIELD)
             * =====================================================
             */
            template: self::nullableString(
                $raw->template
            ),

            /**
             * =====================================================
             * RAW PAYLOAD
             * =====================================================
             */
            payload: $raw->payload,
        );
    }

    private static function string(
        mixed $value,
        string $field,
    ): string {

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
    ): ?string {

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
            return new DateTimeImmutable($dateString);
        } catch (\Exception) {
            throw new \InvalidArgumentException(
                "{$field} must be valid ISO-8601 datetime"
            );
        }
    }
}
