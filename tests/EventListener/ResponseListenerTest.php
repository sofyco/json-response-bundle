<?php declare(strict_types=1);

namespace Sofyco\Bundle\JsonResponseBundle\Tests\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ResponseListenerTest extends WebTestCase
{
    public function testCreated(): void
    {
        $body = \json_encode(['foo' => 'baz']) ?: '';
        $response = $this->sendRequest(Request::METHOD_POST, $body);

        self::assertSame($body, $response->getContent());
        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testCreatedWithoutResponse(): void
    {
        $response = $this->sendRequest(Request::METHOD_POST);

        self::assertSame('', $response->getContent());
        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    public function testGet(): void
    {
        $response = $this->sendRequest();

        self::assertSame('[]', $response->getContent());
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testError(): void
    {
        $response = $this->sendRequest(Request::METHOD_CONNECT);

        self::assertSame('{"message":"Something wrong"}', $response->getContent());
        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
    }

    public function testHttpException(): void
    {
        $response = $this->sendRequest(Request::METHOD_OPTIONS);

        self::assertSame('{"message":"Page not found"}', $response->getContent());
        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testValidationErrors(): void
    {
        $response = $this->sendRequest(Request::METHOD_PUT);

        $message = '{"errors":{"name":{"message":"Name error","parameters":[]},"email":{"message":"Email error","parameters":{"min":3}}}}';

        self::assertSame($message, $response->getContent());
        self::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testMessengerEnvelope(): void
    {
        $response = $this->sendRequest(Request::METHOD_PATCH);

        self::assertSame('{"name":"khaperets"}', $response->getContent());
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    private function sendRequest(string $method = Request::METHOD_GET, ?string $body = null): Response
    {
        $client = self::createClient();

        $client->request($method, '/', [], [], [], $body);

        return $client->getResponse();
    }
}
