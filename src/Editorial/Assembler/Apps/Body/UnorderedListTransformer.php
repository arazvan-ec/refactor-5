<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\LinkExtractor;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\ListItem;
use Ec\Editorial\Domain\Model\Body\UnorderedList;

final readonly class UnorderedListTransformer implements BodyElementTransformer
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
        \assert($element instanceof UnorderedList);

        $elementArray = [
            'type' => $element->type(),
            'items' => [],
        ];

        /** @var ListItem $item */
        foreach ($element as $item) {
            $links = $this->linkExtractor->extract($item);

            $elementArray['items'][] = [
                'type' => $item->type(),
                'content' => $item->content(),
                'links' => $links ?: null,
            ];
        }

        return $elementArray;
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return UnorderedList::class;
    }
}
