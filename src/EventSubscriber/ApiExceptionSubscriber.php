<?php

namespace App\EventSubscriber;

use App\Domain\Email\EmailSendingException;
use App\Shared\Time\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;


/**
 * Это покрывает:
 * Ошибка              Что происходит
 * Валидация           422
 * Шаблон/адрес        400
 * Маршрут не найден   404
 * Метод не разрешён   405
 * Ошибка БД           500
 * Ошибка SMTP         500
 */
class ApiExceptionSubscriber implements EventSubscriberInterface
{

    public function __construct(
        #[Autowire(service: 'monolog.logger.api')]
        private LoggerInterface $logger,
        private ClockInterface $clock
    ) {}


    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onKernelException', 10]];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $status = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $code = 'INTERNAL_SERVER_ERROR';
        $message = 'Внутренняя ошибка сервера';

        switch (true) {

            // Ошибки валидации DTO
            case $exception instanceof \InvalidArgumentException:
                $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
                $code = 'VALIDATION_ERROR';
                $message = $exception->getMessage();
                break;

            // Доменные ошибки email (шаблон, адрес, сеть)
            case $exception instanceof EmailSendingException:
                $status = JsonResponse::HTTP_BAD_REQUEST;
                $code = 'EMAIL_SENDING_ERROR';
                $message = $exception->getMessage();
                break;

            // Ошибка роутера (маршрут не найден)
            case $exception instanceof NotFoundHttpException:
                $status = JsonResponse::HTTP_NOT_FOUND;
                $code = 'ROUTE_NOT_FOUND';
                $message = 'API маршрут не найден';
                break;

            // Другие HTTP ошибки Symfony (403, 405 и т.д.)
            case $exception instanceof HttpExceptionInterface:
                $status = $exception->getStatusCode();
                $code = 'HTTP_ERROR';
                $message = $exception->getMessage();
                break;

            // Ошибки базы
            case $exception instanceof \PDOException:
            case $exception instanceof \Doctrine\DBAL\Exception:
                $status = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
                $code = 'DATABASE_ERROR';
                $message = 'Ошибка соединения с базой данных';
                break;

            // Всё остальное
            default:
                $status = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
                $code = 'INTERNAL_SERVER_ERROR';
                $message = $exception->getMessage();
        }

        /**
         * LOGGING (structured)
         */
        $this->logger->error('API exception', [
            'error_code' => $code,
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'path' => $event->getRequest()->getPathInfo(),
            'method' => $event->getRequest()->getMethod(),
            'trace' => $exception->getTraceAsString(),
        ]);


        $response = new JsonResponse([
            'success' => false,
            'timestamp' => $this->clock->nowFormatted(),
            'error' => [
                'code' => $code,
                'message' => $message,
            ]
        ], $status);

        $event->setResponse($response);
    }
}
