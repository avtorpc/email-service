<?php

declare(strict_types=1);

namespace App\Application\Email\Verification\Mapper;

use App\Application\Email\Verification\Command\VerificationMailCommand;
use App\Application\Email\Verification\DTO\VerificationEmailRequest;
use DateInterval;

final class EmailMapper
{
    public static function map(
        VerificationEmailRequest $request
    ): VerificationMailCommand {

        $expiresAt = $request->occurredAt->add(
            new DateInterval(
                sprintf(
                    'PT%dS',
                    $request->expiresInSeconds
                )
            )
        );

        $template = $request->template;

        if ($template === null || trim($template) === '') {
            $template = $request->eventType;
        }


        return new VerificationMailCommand(

        /**
         * =====================================================
         * EVENT METADATA
         * =====================================================
         */
            eventId: $request->eventId,
            eventType: $request->eventType,
            eventVersion: $request->eventVersion,
            occurredAt: $request->occurredAt,

            /**
             * =====================================================
             * EVENT LIFETIME
             * =====================================================
             */
            expiresAt: $expiresAt,

            /**
             * =====================================================
             * DISTRIBUTED TRACING
             * =====================================================
             */
            traceId: $request->traceId,

            /**
             * =====================================================
             * EVENT SOURCE
             * =====================================================
             */
            correlationId: $request->correlationId,
            producerService: $request->producerService,

            /**
             * =====================================================
             * EMAIL DATA
             * =====================================================
             */
            callback: $request->callback,

            toEmail: $request->recipientEmail,

            toName: trim(
                $request->recipientFirstName
                . ' '
                . $request->recipientLastName
            ),
            expiresInSeconds: $request->expiresInSeconds,

            /**
             * =====================================================
             * TEMPLATE
             * =====================================================
             */
            template: $request->template,

            subject: 'Место — код подтверждения email',

            /**
             * =====================================================
             * TEMPLATE PAYLOAD
             * =====================================================
             */
            templateData: [
                'locale' => $request->locale,
                'expires_at' => $request->payload['expires_at'] ?? $expiresAt->format(DATE_ATOM),

                'verification_code' => $request->verificationCode,

                'expires_in_seconds' => $request->expiresInSeconds,

                'resend_attempts_left' => $request->resendAttemptsLeft,

                'recipient_first_name' => $request->recipientFirstName,

                'recipient_last_name' => $request->recipientLastName,
            ],
        );
    }
}
