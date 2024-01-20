<?php declare(strict_types=1);

namespace Sofyco\Bundle\JsonResponseBundle\Tests\App;

use Sofyco\Bundle\JsonResponseBundle\JsonResponseBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class Kernel extends \Symfony\Component\HttpKernel\Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new JsonResponseBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', ['test' => true]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('index', '/')->controller(__CLASS__);
    }

    public function __invoke(Request $request): mixed
    {
        return match ($request->getMethod()) {
            Request::METHOD_GET => $request->query->all(),
            Request::METHOD_POST => \json_decode((string) $request->getContent(), true),
            Request::METHOD_PUT => throw $this->getValidationException(),
            Request::METHOD_PATCH => $this->createEnvelope(),
            Request::METHOD_OPTIONS => throw new NotFoundHttpException('Page not found'),
            default => throw new \InvalidArgumentException('Something wrong'),
        };
    }

    private function getValidationException(): ValidationFailedException
    {
        $violations = [
            new ConstraintViolation(
                message: 'Name error',
                messageTemplate: '',
                parameters: [],
                root: null,
                propertyPath: 'name',
                invalidValue: '---',
            ),
            new ConstraintViolation(
                message: 'Email error',
                messageTemplate: '',
                parameters: ['min' => 3],
                root: null,
                propertyPath: 'email',
                invalidValue: '@',
            ),
        ];

        return new ValidationFailedException(new \stdClass(), new ConstraintViolationList($violations));
    }

    private function createEnvelope(): Envelope
    {
        $handler = new class {
            public function __invoke(\stdClass $message): array
            {
                return ['name' => $message->name];
            }
        };

        $bus = new MessageBus([
            new HandleMessageMiddleware(new HandlersLocator([
                \stdClass::class => [$handler],
            ])),
        ]);

        $message = new \stdClass();
        $message->name = 'khaperets';

        return $bus->dispatch($message);
    }
}
