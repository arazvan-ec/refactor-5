<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Media\Widget;

use Ec\Widget\Domain\Model\Widget;

interface WidgetTypeTransformer
{
    /**
     * @return array<string, mixed>
     */
    public function transform(Widget $widget): array;

    public function supports(): string;
}
