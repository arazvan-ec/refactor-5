<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Editorial;
use Ec\Tag\Domain\Model\QueryTagClient;
use Ec\Tag\Domain\Model\Tag;
use Psr\Log\LoggerInterface;

final readonly class TagsResolver
{
    public function __construct(
        private QueryTagClient $queryTagClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<Tag>
     */
    public function resolve(Editorial $editorial): array
    {
        $tags = [];

        foreach ($editorial->tags()->getArrayCopy() as $tag) {
            try {
                $tags[] = $this->queryTagClient->findTagById($tag->id());
            } catch (\Throwable $exception) {
                $this->logger->error($exception->getMessage());
                continue;
            }
        }

        return $tags;
    }
}
