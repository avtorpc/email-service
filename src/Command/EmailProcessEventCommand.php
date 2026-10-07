<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Email\Command\StoredEmailCommand;
use App\Application\Email\EmailEventService;
use App\Infrastructure\Email\Sender\SymfonyMailerEmailSender;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:email:process-event',
    description: 'Sends one queued email event by event_id.'
)]
final class EmailProcessEventCommand extends Command
{
    // Общий лимит попыток отправки одного события, включая попытки до перезапуска.
    private const MAX_ATTEMPTS = 5;

    // Пауза между повторными попытками отправки в рамках одного запуска.
    private const RETRY_DELAY_SECONDS = 5;

    public function __construct(
        private readonly EmailEventService $eventService,
        private readonly SymfonyMailerEmailSender $emailSender,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('event_id', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->processEvent((string) $input->getArgument('event_id'), $output);
    }

    /**
     * $emailSent становится true только после новой успешной отправки письма.
     * Уже обработанные и занятые события не учитываются в лимите worker.
     */
    public function processEvent(
        string $eventId,
        OutputInterface $output,
        ?bool &$emailSent = null,
    ): int {
        $emailSent = false;

        if (!$this->eventService->tryLockEvent($eventId)) {
            $this->log($output, sprintf('already_processing event_id=%s', $eventId));
            return Command::SUCCESS;
        }

        try {
            return $this->processLockedEvent($eventId, $output, $emailSent);
        } finally {
            $this->eventService->unlockEvent($eventId);
        }
    }

    private function processLockedEvent(
        string $eventId,
        OutputInterface $output,
        bool &$emailSent,
    ): int
    {

        $this->log($output, sprintf(
            'started event_id=%s max_attempts=%d retry_delay_seconds=%d',
            $eventId,
            self::MAX_ATTEMPTS,
            self::RETRY_DELAY_SECONDS
        ));

        $row = $this->eventService->findByEventId($eventId);

        if ($row === null) {
            $this->log($output, sprintf('not_found event_id=%s', $eventId));

            return Command::FAILURE;
        }

        if ($row['status'] === 'sent') {
            $this->log($output, sprintf(
                'skip_already_sent event_id=%s attempt_count=%d',
                $eventId,
                (int)$row['attempt_count']
            ));

            return Command::SUCCESS;
        }

        $attemptsMade = (int) $row['attempt_count'];
        if ($attemptsMade >= self::MAX_ATTEMPTS) {
            $this->eventService->markFailed($eventId, (string) ($row['last_error'] ?: 'Retry limit reached'));
            $this->log($output, sprintf('exhausted attempts=%d event_id=%s', $attemptsMade, $eventId));
            return Command::FAILURE;
        }

        $command = StoredEmailCommand::fromProcessingRow($row);
        $lastError = null;

        $this->log($output, sprintf(
            'loaded event_id=%s status=%s attempt_count=%d event_type=%s trace_id=%s correlation_id=%s to=%s recipient_name="%s" template=%s subject="%s"',
            $eventId,
            (string)$row['status'],
            (int)$row['attempt_count'],
            (string)$row['event_type'],
            $this->nullable($row['trace_id'] ?? null),
            $this->nullable($row['correlation_id'] ?? null),
            $command->toEmail,
            $command->toName,
            $command->template,
            $command->subject
        ));

        for ($attempt = $attemptsMade + 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $this->log($output, sprintf(
                    'attempt_started attempt=%d/%d event_id=%s to=%s template=%s',
                    $attempt,
                    self::MAX_ATTEMPTS,
                    $eventId,
                    $command->toEmail,
                    $command->template
                ));

                $this->eventService->markProcessing($eventId);
                $this->log($output, sprintf(
                    'status_processing attempt=%d/%d event_id=%s',
                    $attempt,
                    self::MAX_ATTEMPTS,
                    $eventId
                ));

                $this->emailSender->send($command);
                $this->eventService->markSent($eventId);
                $this->log($output, sprintf(
                    'sent attempt=%d/%d event_id=%s to=%s',
                    $attempt,
                    self::MAX_ATTEMPTS,
                    $eventId,
                    $command->toEmail
                ));

                $emailSent = true;
                return Command::SUCCESS;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                if ($attempt === self::MAX_ATTEMPTS) {
                    $this->eventService->markFailed($eventId, $lastError);
                } else {
                    $this->eventService->recordAttemptFailure($eventId, $lastError);
                }

                $this->log($output, sprintf(
                    'attempt_failed attempt=%d/%d event_id=%s exception=%s error="%s"',
                    $attempt,
                    self::MAX_ATTEMPTS,
                    $eventId,
                    $e::class,
                    $lastError
                ));

                if ($attempt < self::MAX_ATTEMPTS) {
                    $this->log($output, sprintf(
                        'retry_wait attempt=%d/%d event_id=%s seconds=%d',
                        $attempt,
                        self::MAX_ATTEMPTS,
                        $eventId,
                        self::RETRY_DELAY_SECONDS
                    ));

                    sleep(self::RETRY_DELAY_SECONDS);
                }
            }
        }

        $this->log($output, sprintf(
            'exhausted attempts=%d event_id=%s last_error="%s"',
            self::MAX_ATTEMPTS,
            $eventId,
            $lastError ?? 'unknown error'
        ));

        return Command::FAILURE;
    }

    private function log(OutputInterface $output, string $message): void
    {
        $output->writeln(sprintf(
            '[%s] [email-worker] %s',
            (new \DateTimeImmutable())->format(DATE_ATOM),
            $message
        ));
    }

    private function nullable(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'null';
        }

        return (string)$value;
    }
}
