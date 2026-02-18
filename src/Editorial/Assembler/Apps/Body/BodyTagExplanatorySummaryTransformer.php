<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagExplanatorySummary;

final readonly class BodyTagExplanatorySummaryTransformer implements BodyElementTransformer
{
    public function __construct(
        private BodyTransformerPipeline $pipeline,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(BodyElement $element, BodyTransformContext $context): array
    {
        \assert($element instanceof BodyTagExplanatorySummary);

        $body = $this->pipeline->transformBody($element->body(), $context);

        return [
            'type' => $element->type(),
            'title' => $element->title(),
            'items' => $body['elements'],
        ];
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return BodyTagExplanatorySummary::class;
    }
}
