<?php

declare(strict_types=1);

namespace App\Infrastructure\Dictionary;

use App\Domain\Dictionary\DictionaryProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Провайдер справочников через HTTP-запросы к mp-dictionaries.
 *
 * Формат ожидаемого ответа:
 * GET /api/refbook/{dictionary-name}
 *
 * --- по факту не используется в данный момент в микросервисе email
 */
final class HttpDictionaryProvider implements DictionaryProviderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $baseUrl,
        private InMemoryDictionaryProvider $fallback,
        private LoggerInterface $logger,
    ) {}

    public function get(string $key, ?string $clientId = null): array
    {
        try {
            $url = rtrim($this->baseUrl, '/') . $key;
            $options = [
                'timeout' => 2.0,
                'headers' => ['Accept' => 'application/json'],
            ];

            if ($clientId !== null) {
                $options['query'] = ['client_id' => $clientId];
            }

            $response = $this->httpClient->request('GET', $url, $options);
            $status = $response->getStatusCode();

            if ($status !== 200) {
                throw new \RuntimeException("Dictionary API returned HTTP {$status}");
            }

            $data = $response->toArray();

            return $data['data'] ?? [];
        } catch (\Throwable $e) {
            $this->logger->warning('Dictionary API unavailable, using fallback', [
                'key' => $key,
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);

            return $this->fallback->get($key, $clientId);
        }
    }
}
