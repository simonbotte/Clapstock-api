<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class RealtimePublisher
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly bool $enabled,
        private readonly LoggerInterface $logger,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public function publishProjectEvent(string $projectCode, string $type, array $payload): void
    {
        if (!$this->enabled) {
            return;
        }

        try {
            $this->hub->publish(new Update(
                sprintf('project/%s', $projectCode),
                json_encode(['type' => $type, 'payload' => $payload], JSON_THROW_ON_ERROR)
            ));
        } catch (\Throwable $exception) {
            $this->logger->warning('Mercure publish failed.', [
                'projectCode' => $projectCode,
                'eventType' => $type,
                'exception' => $exception,
            ]);
        }
    }
}
