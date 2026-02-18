<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Media;

use Ec\Editorial\Domain\Model\Opening;
use Ec\Editorial\Exceptions\MultimediaDataTransformerNotFoundException;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia;

final class MediaTransformerPipeline
{
    /** @var array<class-string, MediaTransformer> */
    private array $transformers = [];

    public function addTransformer(MediaTransformer $transformer): void
    {
        $this->transformers[$transformer->supports()] = $transformer;
    }

    /**
     * @param array<string, array<string, mixed>> $multimediaData
     *
     * @return array<string, mixed>
     *
     * @throws MultimediaDataTransformerNotFoundException
     */
    public function transform(array $multimediaData, Opening $opening): array
    {
        $multimediaId = $opening->multimediaId();

        if (!$multimediaId || empty($multimediaData[$multimediaId])) {
            return [];
        }

        /** @var Multimedia $multimediaElement */
        $multimediaElement = $multimediaData[$multimediaId]['opening'];
        $className = $multimediaElement::class;

        if (!isset($this->transformers[$className])) {
            throw new MultimediaDataTransformerNotFoundException(
                \sprintf('Media data transformer type %s not found', $multimediaElement->type())
            );
        }

        return $this->transformers[$className]->transform($multimediaData, $opening);
    }
}
