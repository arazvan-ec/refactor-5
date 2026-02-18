<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Media;

use Ec\Editorial\Domain\Model\Opening;

interface MediaTransformer
{
    /**
     * @param array<string, mixed> $multimediaData
     *
     * @return array<string, mixed>
     */
    public function transform(array $multimediaData, Opening $opening): array;

    /**
     * @return class-string
     */
    public function supports(): string;
}
