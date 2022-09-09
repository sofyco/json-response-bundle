<?php declare(strict_types=1);

namespace Sofyco\Bundle\JsonResponseBundle\EventListener;


use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class EnvelopeResponseListener
{
    public function onKernelView(ViewEvent $event): void
    {
        $envelope = $event->getControllerResult();

        if (!$envelope instanceof Envelope) {
            return;
        }

        /** @var HandledStamp|null $handledStamp */
        $handledStamp = $envelope->last(HandledStamp::class);

        if (null === $handledStamp) {
            return;
        }

        $event->setControllerResult($handledStamp->getResult());
    }
}
