<?php declare(strict_types=1);

namespace Sofyco\Bundle\JsonResponseBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\SerializerInterface;

final class ControllerResponseListener
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function onKernelView(ViewEvent $event): void
    {
        $data = $event->getControllerResult();
        $statusCode = $this->getStatusCode($data, $event->getRequest()->getMethod());

        $event->setResponse(new JsonResponse($this->serializer->serialize($data, 'json'), $statusCode, [], true));
    }

    private function getStatusCode(mixed $data, string $requestMethod): int
    {
        if (null === $data) {
            return Response::HTTP_NO_CONTENT;
        }

        if (Request::METHOD_POST === $requestMethod) {
            return Response::HTTP_CREATED;
        }

        return Response::HTTP_OK;
    }
}
