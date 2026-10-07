<?php

declare(strict_types=1);

namespace App\Command;

use App\Application\Email\EmailEventService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:email:consume-pending',
    description: 'Processes queued and interrupted email events.'
)]
final class EmailConsumePendingCommand extends Command
{
    // Максимальное количество событий, выбираемых из базы за один запрос.
    private const BATCH_SIZE = 20;

    // Пауза между проверками базы, когда worker продолжает работу.
    private const POLL_SECONDS = 2;

    // Через сколько секунд после последнего обновления прерванное событие можно подобрать повторно.
    private const STALE_AFTER_SECONDS = 60;

    // Максимальное время жизни одного PHP-процесса worker до штатного перезапуска.
    private const MAX_LIFETIME_SECONDS = 300;

    // Максимальное количество успешно отправленных писем одним процессом worker.
    private const MAX_SENT_EMAILS = 100;

    public function __construct(
        private readonly EmailEventService $eventService,
        private readonly EmailProcessEventCommand $processEventCommand,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('once', null, InputOption::VALUE_NONE, 'Process one batch and exit.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startedAt = microtime(true);
        $sentEmails = 0;

        do {
            try {
                $eventIds = $this->eventService->findPendingEventIds(
                    self::BATCH_SIZE,
                    self::STALE_AFTER_SECONDS
                );

                foreach ($eventIds as $eventId) {
                    if ($this->shouldRestart($startedAt, $sentEmails)) {
                        return $this->finishForRestart($output, $startedAt, $sentEmails);
                    }

                    try {
                        $emailSent = false;
                        $this->processEventCommand->processEvent($eventId, $output, $emailSent);
                        if ($emailSent) {
                            $sentEmails++;
                        }
                    } catch (\Throwable $e) {
                        $output->writeln(sprintf(
                            '[email-worker] processing_error event_id=%s error=%s',
                            $eventId,
                            $e->getMessage()
                        ));
                    }

                    if ($this->shouldRestart($startedAt, $sentEmails)) {
                        return $this->finishForRestart($output, $startedAt, $sentEmails);
                    }
                }
            } catch (\Throwable $e) {
                $output->writeln('[email-worker] poll_error ' . $e->getMessage());
            }

            if ($input->getOption('once')) {
                return Command::SUCCESS;
            }

            if ($this->shouldRestart($startedAt, $sentEmails)) {
                return $this->finishForRestart($output, $startedAt, $sentEmails);
            }

            sleep(self::POLL_SECONDS);
        } while (true);
    }

    private function shouldRestart(float $startedAt, int $sentEmails): bool
    {
        return microtime(true) - $startedAt >= self::MAX_LIFETIME_SECONDS
            || $sentEmails >= self::MAX_SENT_EMAILS;
    }

    private function finishForRestart(
        OutputInterface $output,
        float $startedAt,
        int $sentEmails,
    ): int {
        $output->writeln(sprintf(
            '[email-worker] restart_requested lifetime_seconds=%d sent_emails=%d reason=%s',
            (int) (microtime(true) - $startedAt),
            $sentEmails,
            $sentEmails >= self::MAX_SENT_EMAILS ? 'sent_limit' : 'lifetime_limit'
        ));

        return Command::SUCCESS;
    }
}
