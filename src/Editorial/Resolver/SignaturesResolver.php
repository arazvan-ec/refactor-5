<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialBlog;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use Ec\Section\Domain\Model\Section;
use Psr\Log\LoggerInterface;

final readonly class SignaturesResolver
{
    public const TWITTER_TYPES = [EditorialBlog::EDITORIAL_TYPE];

    public function __construct(
        private QueryJournalistClient $queryJournalistClient,
        private JournalistFactory $journalistFactory,
        private JournalistsDataTransformer $journalistsDataTransformer,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function resolve(Editorial $editorial, Section $section): array
    {
        $signatures = [];
        $hasTwitter = \in_array($editorial->editorialType(), self::TWITTER_TYPES, true);

        /** @var Signature $signature */
        foreach ($editorial->signatures()->getArrayCopy() as $signature) {
            $result = $this->retrieveAliasFormat(
                $signature->id()->id(),
                $section,
                $hasTwitter,
            );

            if (!empty($result)) {
                $signatures[] = $result;
            }
        }

        return $signatures;
    }

    /**
     * Resolves journalist data for a single alias ID.
     *
     * @return array<string, mixed>
     */
    public function retrieveAliasFormat(string $aliasId, Section $section, bool $hasTwitter = false): array
    {
        $signature = [];
        $aliasIdModel = $this->journalistFactory->buildAliasId($aliasId);

        try {
            /** @var Journalist $journalist */
            $journalist = $this->queryJournalistClient->findJournalistByAliasId($aliasIdModel);

            $signature = $this->journalistsDataTransformer
                ->write($aliasId, $journalist, $section, $hasTwitter)
                ->read();
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }

        return $signature;
    }
}
