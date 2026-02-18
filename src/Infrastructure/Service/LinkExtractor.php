<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Ec\Editorial\Domain\Model\Body\ElementContentWithLinks;
use Ec\Editorial\Domain\Model\Body\Link;

final readonly class LinkExtractor
{
    /**
     * @return array<string, array<string, string>>
     */
    public function extract(ElementContentWithLinks $element): array
    {
        $result = [];

        /** @var Link $link */
        foreach ($element->links() as $position => $link) {
            $result[$position] = [
                'type' => $link->type(),
                'content' => $link->content(),
                'url' => $link->url(),
                'target' => $link->target(),
            ];
        }

        return $result;
    }
}
