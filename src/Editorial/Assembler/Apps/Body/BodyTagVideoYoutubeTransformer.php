<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagVideoYoutube;

final readonly class BodyTagVideoYoutubeTransformer implements BodyElementTransformer
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
        \assert($element instanceof BodyTagVideoYoutube);

        return [
            'type' => $element->type(),
            'id' => $element->id(),
            'width' => $element->width(),
            'height' => $element->height(),
            'caption' => $element->caption(),
            'start' => $element->start(),
            'video' => \sprintf(
                '%s/embed/video/%s/%s/%s/%s/',
                $this->playerHost,
                $element->id(),
                $element->width(),
                $element->height(),
                $element->start(),
            ),
        ];
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return BodyTagVideoYoutube::class;
    }
}
