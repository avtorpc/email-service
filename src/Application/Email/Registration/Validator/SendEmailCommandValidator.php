<?php

namespace App\Application\Email\Registration\Validator;

use App\Application\Email\Registration\Command\VerificationMailCommand;
use App\Shared\Time\ClockInterface;

final class SendEmailCommandValidator
{
    public function __construct(
        private ClockInterface $clock
    ) {}

    public function validate(VerificationMailCommand $command): array
    {
        $errors = [];

        if ($command->eventId === '') {
            $errors[] = 'event_id is required';
        }

        if ($command->template === '') {
            $errors[] = 'template is required';
        }

        if ($command->toEmail === '' || !filter_var($command->toEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'invalid email';
        }

        if ($command->expiresAt <= $this->clock->now()) {
            $errors[] = 'verification request has expired';
        }

        return $errors;
    }
}
