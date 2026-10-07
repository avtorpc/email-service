<?php

declare(strict_types=1);

namespace App\Application\Email\Registration\Mapper;

use App\Application\Email\Registration\Command\AuthUserMailCommand;
use App\Application\Email\Registration\DTO\AuthUserEmailRequest;

final class EmailMapper
{
    public static function map(AuthUserEmailRequest $request): AuthUserMailCommand
    {
        /**
         * TEMPLATE RESOLUTION RULE:
         * 1. если template задан (строка и не пустая) → используем его
         * 2. иначе → используем eventType
         */
        $template = $request->template;

        if ($template === null || trim($template) === '') {
            $template = $request->eventType;
        }

        return new AuthUserMailCommand(

        /**
         * EVENT METADATA
         */
            eventId: $request->eventId,
            eventType: $request->eventType,
            eventVersion: $request->eventVersion,
            occurredAt: $request->occurredAt,

            /**
             * TRACING
             */
            traceId: $request->traceId,
            correlationId: $request->correlationId,

            /**
             * SOURCE
             */
            producerService: $request->producerService,
            callback: $request->callback,

            /**
             * EMAIL
             */
            toEmail: $request->email,
            toName: trim($request->firstName . ' ' . $request->lastName),

            /**
             * TEMPLATE (RESOLVED)
             */
            template: $template,
            subject: 'Регистрация успешно завершена',

            /**
             * TEMPLATE DATA
             */
            templateData: [
                'user_uuid' => $request->userUuid,
                'email' => $request->email,
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'patronymic' => $request->patronymic,
                'phone_number' => $request->phoneNumber,
                'verification_channel_id' => $request->verificationChannelId,
                'created_at' => $request->createdAt,
                'updated_at' => $request->updatedAt,
            ],
        );
    }
}
