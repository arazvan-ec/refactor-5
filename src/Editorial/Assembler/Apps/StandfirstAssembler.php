<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Standfirst;

final readonly class StandfirstAssembler
{
    public function __construct(
        private BodyTransformerPipeline $bodyTransformerPipeline,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assemble(Standfirst $standfirst, BodyTransformContext $context): array
    {
        $content = $standfirst->content();

        if (null === $content) {
            return [];
        }

        return $this->bodyTransformerPipeline->transformElement($content, $context);
    }
}
