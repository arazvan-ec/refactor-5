<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps;

use App\Infrastructure\Service\UrlGenerator;
use Ec\Encode\Encode;
use Ec\Section\Domain\Model\Section;
use Ec\Tag\Domain\Model\Tag;

final readonly class TagsAssembler
{
    public function __construct(
        private UrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @param array<Tag> $tags
     *
     * @return array<int, array{id: string, name: string, url: string}>
     */
    public function assemble(array $tags, Section $section): array
    {
        $result = [];

        foreach ($tags as $tag) {
            $urlPath = \sprintf(
                '/tags/%s/%s-%s',
                Encode::encodeUrl($tag->type()->name()),
                Encode::encodeUrl($tag->name()),
                $tag->id()->id(),
            );

            $result[] = [
                'id' => $tag->id()->id(),
                'name' => $tag->name(),
                'url' => $this->urlGenerator->generateUrl(
                    'https://%s.%s.%s/%s',
                    'www',
                    $section->siteId(),
                    $urlPath,
                ),
            ];
        }

        return $result;
    }
}
