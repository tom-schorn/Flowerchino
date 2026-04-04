<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RateLimitSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RateLimiterFactory $apiPublicLimiter,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST  => ['onRequest', 8],
            KernelEvents::RESPONSE => ['onResponse', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/v1/')) {
            return;
        }

        $ip      = $request->getClientIp() ?? 'unknown';
        $limiter = $this->apiPublicLimiter->create($ip);
        $limit   = $limiter->consume(1);

        $headers = [
            'X-RateLimit-Limit'     => 60,
            'X-RateLimit-Remaining' => max(0, $limit->getRemainingTokens()),
            'X-RateLimit-Reset'     => $limit->getRetryAfter()->getTimestamp(),
        ];

        if (!$limit->isAccepted()) {
            $response = new JsonResponse(
                ['status' => 'error', 'data' => null, 'error' => 'Rate limit exceeded. Max 60 requests per minute.'],
                429,
                $headers
            );
            $event->setResponse($response);
            return;
        }

        // Attach headers to be added to the real response later
        $request->attributes->set('_rate_limit_headers', $headers);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $headers = $event->getRequest()->attributes->get('_rate_limit_headers');
        if (!$headers) {
            return;
        }
        foreach ($headers as $name => $value) {
            $event->getResponse()->headers->set($name, (string) $value);
        }
    }
}
