<?php declare(strict_types=1);

namespace Sofyco\Bundle\JsonResponseBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ExceptionResponseListener
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $data = [];
        $throwable = $event->getThrowable();

        if ($throwable instanceof HandlerFailedException) {
            $throwable = \current($throwable->getNestedExceptions()) ?: $throwable;
        }

        $statusCode = $this->getStatusCode($throwable);

        if ($throwable instanceof ValidationFailedException) {
            /** @var ConstraintViolationInterface $violation */
            foreach ($throwable->getViolations() as $violation) {
                $data['errors'][$violation->getPropertyPath()] = [
                    'message' => $violation->getMessage(),
                    'parameters' => $violation->getParameters(),
                ];
            }
        } else {
            $data['message'] = $throwable->getMessage();
        }

        $event->setResponse(new JsonResponse($this->serializer->serialize($data, 'json'), $statusCode, [], true));
    }

    private function getStatusCode(\Throwable $throwable): int
    {
        if ($throwable instanceof ValidationFailedException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            return $throwable->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }
}
