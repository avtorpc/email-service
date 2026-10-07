<?php

declare(strict_types=1);

namespace App\Application\Email\Registration\DTO;

final class AuthUserEmailRawRequest
{
    /**
     * =========================
     * ENVELOPE
     * =========================
     */
    public mixed $eventId = null;
    public mixed $eventType = null;
    public mixed $eventVersion = null;
    public mixed $occurredAt = null;

    /**
     * =========================
     * TRACING
     * =========================
     */
    public mixed $traceId = null;
    public mixed $correlationId = null;

    /**
     * =========================
     * PRODUCER
     * =========================
     */
    public mixed $producerService = null;
    public mixed $callback = null;

    /**
     * =========================
     * PAYLOAD (FLAT USER EVENT)
     * =========================
     */
    public mixed $userUuid = null;
    public mixed $email = null;
    public mixed $firstName = null;
    public mixed $lastName = null;
    public mixed $patronymic = null;
    public mixed $phoneNumber = null;
    public mixed $verificationChannelId = null;
    public mixed $createdAt = null;
    public mixed $updatedAt = null;

    /**
     * OPTIONAL BUSINESS FIELDS
     */
    public mixed $template = null;

    /**
     * RAW DEBUG
     */
    public array $payload = [];
}
