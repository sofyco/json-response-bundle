<?php declare(strict_types=1);

namespace Sofyco\Bundle\JsonResponseBundle\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator;

final readonly class ExceptionResponseListener
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $data = [];
        $throwable = $event->getThrowable();

        if ($throwable instanceof Messenger\Exception\HandlerFailedException) {
            $throwable = \current($throwable->getNestedExceptions()) ?: $throwable;
        }

        $statusCode = $this->getStatusCode($throwable);
        $violations = $this->getValidationViolations($throwable);

        if (null !== $violations) {
            foreach ($violations as $violation) {
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
        if (null !== $this->getValidationViolations($throwable)) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            return $throwable->getStatusCode();
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    private function getValidationViolations(\Throwable $throwable): ?Validator\ConstraintViolationListInterface
    {
        if ($throwable instanceof Messenger\Exception\ValidationFailedException || $throwable instanceof Validator\Exception\ValidationFailedException) {
            return $throwable->getViolations();
        }

        return null;
    }
}
