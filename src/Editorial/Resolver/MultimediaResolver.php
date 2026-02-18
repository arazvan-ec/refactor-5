<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Infrastructure\Service\MultimediaImageService;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use GuzzleHttp\Promise\Utils;
use Http\Promise\Promise;

final readonly class MultimediaResolver
{
    public function __construct(
        private QueryMultimediaClient $queryMultimediaClient,
        private MultimediaImageService $multimediaImageService,
    ) {
    }

    /**
     * Creates async promises for the given multimedia IDs without resolving them.
     *
     * @param array<string> $multimediaIds
     *
     * @return array<int, Promise>
     */
    public function startAsync(array $multimediaIds): array
    {
        $promises = [];

        foreach ($multimediaIds as $multimediaId) {
            $promises[] = $this->queryMultimediaClient->findMultimediaById($multimediaId, true);
        }

        return $promises;
    }

    /**
     * Settles all promises in a single batch and filters fulfilled results.
     *
     * @param array<int, Promise> $promises
     *
     * @return array<string, AbstractMultimedia>
     */
    public function settle(array $promises): array
    {
        if (empty($promises)) {
            return [];
        }

        /** @var array<int, array{state: string, value?: AbstractMultimedia}> $settled */
        $settled = Utils::settle($promises)
            ->then(static function (array $results): array {
                return $results;
            })
            ->wait(true);

        return $this->filterFulfilled($settled);
    }

    /**
     * Shortcut: start async promises for an editorial's multimedia and settle immediately.
     *
     * @return array<string, AbstractMultimedia>
     */
    public function resolveForEditorial(Editorial $editorial): array
    {
        $multimediaId = $this->multimediaImageService->getMultimediaId($editorial->multimedia());

        if (null === $multimediaId || $editorial->multimedia() instanceof Widget) {
            return [];
        }

        $promises = $this->startAsync([$multimediaId->id()]);

        return $this->settle($promises);
    }

    /**
     * Starts async multimedia resolution for an editorial, encapsulating the Widget check.
     *
     * Returns a MultimediaResolutionResult with promises (or empty if Widget/no multimedia).
     */
    public function startAsyncForEditorial(Editorial $editorial): MultimediaResolutionResult
    {
        $multimediaId = $this->multimediaImageService->getMultimediaId($editorial->multimedia());

        if (null === $multimediaId || $editorial->multimedia() instanceof Widget) {
            return new MultimediaResolutionResult();
        }

        $promises = $this->startAsync([$multimediaId->id()]);

        return new MultimediaResolutionResult(promises: $promises);
    }

    /**
     * Extracts the multimedia ID string from an editorial, if available.
     */
    private function extractMultimediaId(Editorial $editorial): ?string
    {
        $multimediaId = $this->multimediaImageService->getMultimediaId($editorial->multimedia());

        return $multimediaId?->id();
    }

    /**
     * Filters settled promises, keeping only fulfilled ones indexed by their multimedia ID.
     *
     * @param array<int, array{state: string, value?: AbstractMultimedia}> $settled
     *
     * @return array<string, AbstractMultimedia>
     */
    private function filterFulfilled(array $settled): array
    {
        $result = [];

        foreach ($settled as $promise) {
            if (Promise::FULFILLED === $promise['state'] && isset($promise['value'])) {
                /** @var AbstractMultimedia $multimedia */
                $multimedia = $promise['value'];
                $result[$multimedia->id()] = $multimedia;
            }
        }

        return $result;
    }
}
