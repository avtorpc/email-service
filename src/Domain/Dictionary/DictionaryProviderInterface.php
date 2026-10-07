<?php

declare(strict_types=1);

namespace App\Domain\Dictionary;

/**
 * Абстракция над источником справочников.
 * Реализация может ходить в mp-backend по HTTP (HttpDictionaryProvider)
 * или отдавать fallback-значения из памяти (InMemoryDictionaryProvider).
 */
interface DictionaryProviderInterface
{
    /**
     * Получить справочник по ключу.
     *
     * @param string $key Например: 'retry_policy', 'email_templates', 'smtp_config'
     * @param string|null $clientId Идентификатор клиента (для клиентских переопределений)
     * @return array<string, mixed>
     */
    public function get(string $key, ?string $clientId = null): array;
}
