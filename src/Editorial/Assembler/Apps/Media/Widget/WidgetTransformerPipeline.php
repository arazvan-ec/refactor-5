<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Media\Widget;

use Ec\Widget\Domain\Model\Widget;
use Ec\Widget\Exceptions\WidgetDataTransformerAlreadyExistsException;
use Ec\Widget\Exceptions\WidgetDataTransformerNotFoundException;

final class WidgetTransformerPipeline
{
    /** @var array<string, WidgetTypeTransformer> */
    private array $transformers = [];

    /**
     * @throws WidgetDataTransformerAlreadyExistsException
     */
    public function addTransformer(WidgetTypeTransformer $transformer): void
    {
        $widgetType = $transformer->supports();

        if (isset($this->transformers[$widgetType])) {
            throw new WidgetDataTransformerAlreadyExistsException(
                \sprintf('Data transformer for widget type %s already exists', $widgetType)
            );
        }

        $this->transformers[$widgetType] = $transformer;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws WidgetDataTransformerNotFoundException
     */
    public function transform(Widget $widget): array
    {
        $widgetType = $widget->type();

        if (!$widgetType || empty($this->transformers[$widgetType])) {
            throw new WidgetDataTransformerNotFoundException(
                \sprintf('No data transformer found for widget type %s', $widgetType ?: 'unknown')
            );
        }

        return $this->transformers[$widgetType]->transform($widget);
    }
}
