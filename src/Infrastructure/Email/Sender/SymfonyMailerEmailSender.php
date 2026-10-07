<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Sender;

use App\Application\Email\Contract\EmailCommandMessageInterface;
use App\Application\Email\Verification\Command\VerificationMailCommand;
use App\Infrastructure\Template\EmailTemplateResolver;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

final class SymfonyMailerEmailSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly EmailTemplateResolver $templateResolver,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {}

    public function send(
        EmailCommandMessageInterface $command
    ): void {

        /**
         * =====================================================
         * RESOLVE TEMPLATE
         * =====================================================
         */
        $template = $this->templateResolver->resolve(
            $command->template
        );

        /**
         * =====================================================
         * RENDER TEMPLATE
         * =====================================================
         */
        $html = $this->twig->render(
            $template,
            $command->templateData,
        );

        /**
         * =====================================================
         * BUILD EMAIL
         * =====================================================
         */
        $email = (new Email())
            ->from(new Address(
                $this->fromEmail,
                $this->fromName
            ))
            ->to(new Address(
                $command->toEmail,
                $command->toName
            ))
            ->subject($command->subject)
            ->html($html);

        $this->mailer->send($email);
    }
}
