<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ KernelEvents::RESPONSE => 'onResponse' ];
    }

    public function onResponse(ResponseEvent $event): void
    {
        $resp = $event->getResponse();
        $resp->headers->set('Access-Control-Allow-Origin', 'http://localhost:4200');
        $resp->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
        $resp->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
