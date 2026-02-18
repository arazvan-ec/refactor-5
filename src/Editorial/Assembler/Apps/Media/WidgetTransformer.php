<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Media;

use App\Editorial\Assembler\Apps\Media\Widget\WidgetTransformerPipeline;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaWidget;
use Ec\Widget\Domain\Model\Widget;

final readonly class WidgetTransformer implements MediaTransformer
{
    public function __construct(
        private WidgetTransformerPipeline $widgetTransformerPipeline,
    ) {
    }

    /**
     * @param array<string, mixed> $multimediaData
     *
     * @return array<string, mixed>
     */
    public function transform(array $multimediaData, Opening $opening): array
    {
        $multimediaId = $opening->multimediaId();

        if (!$multimediaId || empty($multimediaData[$multimediaId])) {
            return [];
        }

        /** @var MultimediaWidget $multimedia */
        $multimedia = $multimediaData[$multimediaId]['opening'];
        /** @var Widget $resource */
        $resource = $multimediaData[$multimediaId]['resource'];

        $specificWidgetTypeData = $this->widgetTransformerPipeline->transform($resource);

        return [
            'type' => MultimediaWidget::TYPE,
            'caption' => $multimedia->caption(),
            ...$specificWidgetTypeData,
        ];
    }

    /**
     * @return class-string
     */
    public function supports(): string
    {
        return MultimediaWidget::class;
    }
}
