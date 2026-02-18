<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Link;

final readonly class LinkTransformer implements BodyElementTransformer
{
    /**
     * @return array<string, mixed>
     */
    public function transform(BodyElement $element, BodyTransformContext $context): array
    {
        \assert($element instanceof Link);

        return [
            'type' => $element->type(),
            'content' => $element->content(),
        ];
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return Link::class;
    }
}
