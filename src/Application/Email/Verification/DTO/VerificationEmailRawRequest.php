<?php

namespace App\Application\Email\Verification\DTO;

final class VerificationEmailRawRequest
{
    public mixed $eventId = null;

    public mixed $eventType = null;

    public mixed $eventVersion = null;

    public mixed $occurredAt = null;

    public mixed $traceId = null;

    public mixed $correlationId = null;

    public mixed $producerService = null;

    public mixed $callback = null;

    public mixed $requestId = null;

    public mixed $template = null;

    public mixed $locale = null;

    public mixed $verificationCode = null;

    public mixed $recipientEmail = null;

    public mixed $recipientFirstName = null;

    public mixed $recipientLastName = null;

    public mixed $expiresInSeconds = null;

    public mixed $resendAttemptsLeft = null;

    public array $payload = [];
}
