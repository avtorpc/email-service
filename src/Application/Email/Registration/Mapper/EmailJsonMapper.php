<?php

declare(strict_types=1);

namespace App\Application\Email\Registration\Mapper;

use App\Application\Email\Registration\DTO\AuthUserEmailRawRequest;

final class EmailJsonMapper
{
    public static function fromJson(string $json): AuthUserEmailRawRequest
    {
        $data = json_decode($json, true);

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Invalid JSON payload');
        }

        $payload = $data['payload'] ?? [];
        $producer = $data['producer'] ?? [];

        $dto = new AuthUserEmailRawRequest();

        // ENVELOPE
        $dto->eventId = $data['event_id'] ?? null;
        $dto->eventType = $data['event_type'] ?? null;
        $dto->eventVersion = $data['event_version'] ?? null;
        $dto->occurredAt = $data['occurred_at'] ?? null;

        // TRACING
        $dto->traceId = $data['trace_id'] ?? null;
        $dto->correlationId = $data['correlation_id'] ?? null;

        // PRODUCER
        $dto->producerService = $producer['service'] ?? null;
        $dto->callback = $data['callback'] ?? null;

        // PAYLOAD
        $dto->userUuid = $payload['user_uuid'] ?? null;
        $dto->email = $payload['email'] ?? null;
        $dto->firstName = $payload['first_name'] ?? null;
        $dto->lastName = $payload['last_name'] ?? null;
        $dto->patronymic = $payload['patronymic'] ?? null;
        $dto->phoneNumber = $payload['phone_number'] ?? null;
        $dto->verificationChannelId = $payload['verification_channel_id'] ?? null;
        $dto->createdAt = $payload['created_at'] ?? null;
        $dto->updatedAt = $payload['updated_at'] ?? null;

        // OPTIONAL TEMPLATE (if ever comes in payload)
        $dto->template = $payload['template'] ?? null;

        $dto->payload = $payload;

        return $dto;
    }
}
