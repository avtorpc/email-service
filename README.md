# ONMI Email Service

`email-service` принимает события отправки писем по HTTP от Java `event-worker-service`.
Сам сервис не читает Kafka. После сохранения события в PostgreSQL он возвращает
HTTP `202 Accepted`. Фоновая команда в том же контейнере подбирает задания из базы.

## Запуск

Сервис входит в общую Docker Compose сборку из `ONMI_infra`:

```bash
cd ONMI_infra
make up
make migrate
```

`make bootstrap` запускает окружение и миграции вместе. Переменные подключения к
PostgreSQL и SMTP передаются контейнеру через `ONMI_infra/compose/22-php-email.yml`.
Для отправки писем должны быть настроены `MAILER_DSN`, `MAIL_FROM_ADDRESS` и
`MAIL_FROM_NAME` в окружении оркестрации.

Отдельный контейнер для worker не требуется: при запуске `email-service` рядом
с PHP-FPM стартует `app:email:consume-pending`. Если команда завершится, цикл
запуска в контейнере поднимет ее снова.

Один процесс worker работает не более пяти минут или до отправки 100 писем —
что наступит раньше. Лимиты проверяются между письмами, поэтому выполняемая
отправка не обрывается. После штатного завершения внешний цикл ждет две секунды
и запускает новый PHP-процесс с очищенной памятью.

## Прием события

Маршруты, на которые Java worker пересылает полный JSON события:

| Событие | HTTP-маршрут |
| --- | --- |
| `email.send.requested` | `POST /email/verification/send` |
| `email.send.registration_completed` | `POST /email/registration/completed` |

Маршрутизация событий задана в `ONMI_infra/config/routing.yml`. Общая оболочка
события содержит `event_id`, `event_type`, `event_version`, `occurred_at`,
`trace_id`, `correlation_id`, `producer`, `callback` и `payload`.

Прием запроса проходит в следующем порядке:

1. Контроллер преобразует и проверяет JSON.
2. Событие сохраняется в `email.email_processing_log` со статусом `queued`;
   входящий вызов записывается в `email.raw_event_log`.
3. Контроллер возвращает HTTP `202 Accepted`:

```json
{
  "status": "accepted",
  "event_id": "439e835e-c851-4d02-a312-3fbfe580a778",
  "duplicate": false
}
```

Ответ `202` подтверждает сохранение события, а не доставку письма. Повторный
запрос с тем же `event_id` получает `duplicate: true`. Сохраненное задание
остается доступно worker независимо от повторных HTTP-вызовов.

## Отправка письма

Команда `app:email:consume-pending` выбирает `queued` события и записи,
оставшиеся в `processing` после сбоя более одной минуты. Она передает каждое
событие в `app:email:process-event`. Та удерживает блокировку на время обработки,
восстанавливает данные письма, рендерит Twig-шаблон и отправляет его через
Symfony Mailer. После успешной отправки статус становится `sent`.

При ошибке выполняется до пяти попыток суммарно, в том числе после
перезапуска, с паузой пять секунд между попытками в одном запуске.
`attempt_count` хранится в `email.email_processing_log`. После промежуточной
ошибки событие остается в `processing`; статус `failed` ставится после пятой
неудачной попытки. Вывод фоновой команды доступен в логах контейнера:

```bash
docker logs email_service_php
```

Для диагностики конкретного события:

```sql
SELECT event_id, status, attempt_count, last_error, sent_at, failed_at
FROM email.email_processing_log
WHERE event_id = '<event_id>';
```

Команда для ручного запуска обработки уже сохраненного события (подставьте
нужный `event_id`):

```bash
docker exec email_service_php php bin/console app:email:process-event 439e835e-c851-4d02-a312-3fbfe580a778
```

Если контейнер остановится после ответа `202`, но до завершения отправки,
событие останется в базе. При следующем запуске worker подберет `queued` сразу,
а прерванное `processing` — через минуту после последнего обновления.
Записи со статусом `failed` автоматически не повторяются.

## Основные файлы

- `src/Controller/Email/Verification/VerificationEmailController.php` — прием
  события с кодом подтверждения.
- `src/Controller/Email/Registration/RegistrationCompleteEmailController.php` —
  прием события о завершении регистрации.
- `src/Infrastructure/Email/Repository/PostgresEmailEventRepository.php` —
  сохранение события, дедупликация и статусы обработки.
- `src/Command/EmailConsumePendingCommand.php` — подбор сохраненных заданий.
- `src/Command/EmailProcessEventCommand.php` — попытки отправки и логирование.
- `src/Infrastructure/Email/Sender/SymfonyMailerEmailSender.php` — рендеринг
  шаблона и передача письма в Symfony Mailer.
