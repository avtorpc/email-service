<?php

namespace App\Controller\Email\Verification;

use App\Application\Email\EmailEventService;
use App\Application\Email\Verification\Mapper\EmailDomainMapper;
use App\Application\Email\Verification\Mapper\EmailJsonMapper;
use App\Application\Email\Verification\Mapper\EmailMapper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/verification')]
final class VerificationEmailController
{
    public function __construct(
        private EmailEventService $eventService,
    ) {}

    #[Route('/send', name:"VerificationEmailSendCode", methods: ['POST'])]
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
