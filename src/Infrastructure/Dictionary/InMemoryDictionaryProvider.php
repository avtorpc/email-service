<?php

declare(strict_types=1);

namespace App\Infrastructure\Dictionary;

use App\Domain\Dictionary\DictionaryProviderInterface;

/**
 * Fallback-провайдер справочников.
 * Отдаёт захардкоженные значения, если mp-backend недоступен
 * или пока интеграция с ним не реализована.
 */
final class InMemoryDictionaryProvider implements DictionaryProviderInterface
{
    public function __construct(
        private array $dictionaries = [],
    ) {}

    public function get(string $key, ?string $clientId = null): array
    {
        $defaults = [
            'retry_policy' => [
                'max_retries' => 3,
                'delay_ms' => 60000,
                'exponential_backoff' => true,
                'multiplier' => 2.0,
            ],
            'email_templates' => [
                'verification_code' => [
                    'from_email' => 'noreply@onmi.dev',
                    'from_name' => 'Onmi',
                    'subject' => 'Код подтверждения Onmi',
                ],
            ],
            'smtp_config' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'encryption' => 'tls',
            ],
        ];

        $merged = array_merge($defaults, $this->dictionaries);

        if (!isset($merged[$key])) {
            return [];
        }

        $value = $merged[$key];

        // Если есть клиентское переопределение — применяем
        if ($clientId !== null && is_array($value) && isset($value[$clientId])) {
            return $value[$clientId];
        }

        return $value;
    }
}
