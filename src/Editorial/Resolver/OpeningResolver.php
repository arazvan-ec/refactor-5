<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Orchestrator\Chain\Multimedia\MultimediaOrchestratorHandler;
use App\Orchestrator\Exceptions\OrchestratorTypeNotExistException;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\NewsBase;
use Ec\Infrastructure\Client\Exceptions\InvalidBodyException;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Infrastructure\Client\Http\Media\QueryMultimediaClient as QueryMultimediaOpeningClient;
use Psr\Log\LoggerInterface;

final readonly class OpeningResolver
{
    public function __construct(
        private QueryMultimediaOpeningClient $queryMultimediaOpeningClient,
        private MultimediaOrchestratorHandler $multimediaOrchestratorHandler,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Resolves the opening multimedia for an editorial.
     *
     * @return array<string, array<string, mixed>>
     */
    public function resolve(Editorial $editorial): array
    {
        /** @var NewsBase $editorial */
        $opening = $editorial->opening();

        if (empty($opening->multimediaId())) {
            return [];
        }

        try {
            /** @var AbstractMultimedia $multimedia */
            $multimedia = $this->queryMultimediaOpeningClient->findMultimediaById($opening->multimediaId());

            return $this->multimediaOrchestratorHandler->handler($multimedia);
        } catch (OrchestratorTypeNotExistException|InvalidBodyException $e) {
            $this->logger->warning($e->getMessage());
        }

        return [];
    }
}
