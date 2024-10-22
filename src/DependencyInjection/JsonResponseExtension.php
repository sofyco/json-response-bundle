<?php declare(strict_types=1);

namespace Sofyco\Bundle\JsonResponseBundle\DependencyInjection;

use Sofyco\Bundle\JsonResponseBundle\EventListener\ControllerResponseListener;
use Sofyco\Bundle\JsonResponseBundle\EventListener\EnvelopeResponseListener;
use Sofyco\Bundle\JsonResponseBundle\EventListener\ExceptionResponseListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\HttpKernel\KernelEvents;

final class JsonResponseExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->addListener($container, EnvelopeResponseListener::class, KernelEvents::VIEW);
        $this->addListener($container, ControllerResponseListener::class, KernelEvents::VIEW);
        $this->addListener($container, ExceptionResponseListener::class, KernelEvents::EXCEPTION, -1);
    }

    private function addListener(ContainerBuilder $container, string $className, string $eventName, int $priority = 0): void
    {
        $listener = new Definition($className);
        $listener->setAutowired(true);
        $listener->addTag('kernel.event_listener', ['event' => $eventName, 'priority' => $priority]);

        $container->setDefinition($className, $listener);
    }
}
