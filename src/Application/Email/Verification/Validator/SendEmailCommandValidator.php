<?php

namespace App\Application\Email\Verification\Validator;

use App\Application\Email\Contract\EmailCommandMessageInterface;
use App\Application\Email\Verification\Command\VerificationMailCommand;
use App\Shared\Time\ClockInterface;

final class SendEmailCommandValidator
{
    public function __construct(
        private ClockInterface $clock
    ) {}

    public function validate(EmailCommandMessageInterface $command): array
    {
        $errors = [];

        if ($command->getEventId() === '') {
            $errors[] = 'event_id is required';
        }

        if ($command->getTemplate() === '') {
            $errors[] = 'template is required';
        }

        if ($command->getEmail() === '' || !filter_var($command->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'invalid email';
        }

        // Если expiresAt = null, считаем валидным (срок не истек)
        if ($command->getExpiresAt() !== null && $command->getExpiresAt() <= $this->clock->now()) {
            $errors[] = 'verification request has expired';
        }

        return $errors;
    }
}
