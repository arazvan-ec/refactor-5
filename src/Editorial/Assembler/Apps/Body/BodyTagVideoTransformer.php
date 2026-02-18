<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagVideo;

final readonly class BodyTagVideoTransformer implements BodyElementTransformer
{
    public function __construct(
        private string $playerHost,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(BodyElement $element, BodyTransformContext $context): array
    {
        \assert($element instanceof BodyTagVideo);

        return [
            'type' => $element->type(),
            'id' => $element->id()->id(),
            'width' => $element->width(),
            'height' => $element->height(),
            'caption' => $element->caption(),
            'video' => \sprintf(
                '%s/embed/video/%s/%s/%s/',
                $this->playerHost,
                $element->id()->id(),
                $element->width(),
                $element->height(),
            ),
        ];
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return BodyTagVideo::class;
    }
}
