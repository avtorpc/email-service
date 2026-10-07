<?php

namespace App\Tests\Integration\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmailControllerTest extends WebTestCase
{
    public function testGetIndexReturnsServiceInfo(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('email-service', $data['service']);
        $this->assertSame('1.0.0', $data['version']);
        $this->assertSame('ok', $data['status']);
    }

    public function testGetStatusReturnsHealthy(): void
    {
        $client = static::createClient();
        $client->request('GET', '/status');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('healthy', $data['status']);
        $this->assertSame('email-service', $data['service']);
    }

    public function testPostSendRequiresTo(): void
    {
        $client = static::createClient();
        $client->request('POST', '/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'template' => 'verification_code',
            'client' => 'test',
            'context' => [],
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertSame('VALIDATION_ERROR', $data['error']['code']);
    }

    public function testPostSendRequiresTemplate(): void
    {
        $client = static::createClient();
        $client->request('POST', '/send', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'to' => 'test@example.com',
            'client' => 'test',
            'context' => [],
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($data['success']);
    }
}
