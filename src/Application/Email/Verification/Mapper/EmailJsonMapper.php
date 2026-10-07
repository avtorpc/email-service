<?php

namespace App\Application\Email\Verification\Mapper;

use App\Application\Email\Verification\DTO\VerificationEmailRawRequest;

final class EmailJsonMapper
{
    public static function fromJson(
        string $json
    ): VerificationEmailRawRequest {

        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException(
                'Invalid JSON payload'
            );
        }

        $payload = $data['payload'] ?? [];

        $recipient = $payload['recipient'] ?? [];

        $producer = $data['producer'] ?? [];

        $dto = new VerificationEmailRawRequest();

        $dto->eventId = $data['event_id'] ?? null;

        $dto->eventType = $data['event_type'] ?? null;

        $dto->eventVersion = $data['event_version'] ?? null;

        $dto->occurredAt = $data['occurred_at'] ?? null;

        $dto->traceId = $data['trace_id'] ?? null;

        $dto->correlationId = $data['correlation_id'] ?? null;

        $dto->producerService = $producer['service'] ?? null;

        $dto->callback = $data['callback'] ?? null;

        $dto->requestId = $payload['request_id'] ?? null;

        $dto->template = $payload['template'] ?? null;

        $dto->locale = $payload['locale'] ?? null;

        $dto->verificationCode = $payload['verification_code'] ?? null;

        $dto->recipientEmail = $recipient['email'] ?? null;

        $dto->recipientFirstName = $recipient['first_name'] ?? null;

        $dto->recipientLastName = $recipient['last_name'] ?? null;

        $dto->expiresInSeconds = $payload['expires_in_seconds'] ?? null;

        $dto->resendAttemptsLeft = $payload['resend_attempts_left'] ?? null;

        $dto->payload = $payload;

        return $dto;
    }
}
