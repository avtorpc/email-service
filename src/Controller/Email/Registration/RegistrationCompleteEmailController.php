<?php

namespace App\Controller\Email\Registration;

use App\Application\Email\EmailEventService;
use App\Application\Email\Registration\Mapper\EmailDomainMapper;
use App\Application\Email\Registration\Mapper\EmailJsonMapper;
use App\Application\Email\Registration\Mapper\EmailMapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationCompleteEmailController
{
    public function __construct(
        private EmailEventService $eventService,
        private LoggerInterface $logger
    ) {}

    #[Route('/registration/completed', name:"RegistrationCompleteEmail", methods: ['POST'])]
    public function send(Request $request): JsonResponse
    {
        /**
         * =========================================================
         * 1. JSON → RAW DTO
         * =========================================================
         */
        $raw = EmailJsonMapper::fromJson($request->getContent());

        /**
         * =========================================================
         * 2. RAW → DOMAIN DTO
         * =========================================================
         */
        $dto = EmailDomainMapper::map($raw);

        /**
         * =========================================================
         * 3. DOMAIN → COMMAND
         * =========================================================
         */
        $command = EmailMapper::map($dto);

        $this->logger->debug('Object', [
            'object' => $command,
        ]);

        /**
         * =========================================================
         * 4. INGEST (VALIDATE + DB + IDEMPOTENCY)
         * =========================================================
         */
        $duplicate = $this->eventService->ingest($command);

        /**
         * =========================================================
         * 5. ACK AFTER PERSISTENCE
         * =========================================================
         */
        return new JsonResponse([
            'status' => 'accepted',
            'event_id' => $command->eventId,
            'duplicate' => $duplicate,
        ], 202);
    }
}
