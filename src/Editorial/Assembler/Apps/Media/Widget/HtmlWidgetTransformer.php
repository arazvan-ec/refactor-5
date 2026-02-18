<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Media\Widget;

use Ec\Widget\Domain\Model\HtmlWidget;
use Ec\Widget\Domain\Model\Widget;

final readonly class HtmlWidgetTransformer implements WidgetTypeTransformer
{
    /**
     * @return array<string, mixed>
     */
    public function transform(Widget $widget): array
    {
        if (!$widget instanceof HtmlWidget) {
            return [];
        }

        return [
            'url' => $widget->url() ?: null,
            'aspectRatio' => $this->calculateAspectRatio($widget->params()),
        ];
    }

    public function supports(): string
    {
        return 'html';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function calculateAspectRatio(array $params): ?float
    {
        if (empty($params['aspect-ratio']) || !\is_string($params['aspect-ratio'])) {
            return null;
        }

        $aspectRatioValue = $params['aspect-ratio'];

        if (!str_contains($aspectRatioValue, '/')) {
            return null;
        }

        $parts = explode('/', $aspectRatioValue);

        if (2 !== \count($parts)) {
            return null;
        }

        $numerator = trim($parts[0]);
        $denominator = trim($parts[1]);

        if (!is_numeric($numerator) || !is_numeric($denominator)) {
            return null;
        }

        $denominatorFloat = (float) $denominator;

        if (0.0 === $denominatorFloat) {
            return null;
        }

        return round((float) $numerator / $denominatorFloat, 1);
    }
}
