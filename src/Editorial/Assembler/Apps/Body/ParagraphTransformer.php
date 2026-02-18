<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\LinkExtractor;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\Paragraph;

final readonly class ParagraphTransformer implements BodyElementTransformer
{
    public function __construct(
        private LinkExtractor $linkExtractor,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(BodyElement $element, BodyTransformContext $context): array
    {
        \assert($element instanceof Paragraph);

        $links = $this->linkExtractor->extract($element);

        return [
            'type' => $element->type(),
            'content' => $element->content(),
            'links' => $links ?: null,
        ];
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return Paragraph::class;
    }
}
