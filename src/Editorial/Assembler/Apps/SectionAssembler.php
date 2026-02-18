<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps;

use App\Infrastructure\Service\UrlGenerator;
use Ec\Section\Domain\Model\Section;

final readonly class SectionAssembler
{
    public function __construct(
        private UrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @return array{id: string, name: string, url: string, encodeName: string}
     */
    public function assembleSection(Section $section): array
    {
        $url = $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $section->getPath()
        );

        return [
            'id' => $section->id()->id(),
            'name' => $section->name(),
            'url' => $url,
            'encodeName' => $section->encodeName(),
        ];
    }

    /**
     * @return array<int, array{id: string, name: string, url: string, encodeName: string}>
     */
    public function assembleOptions(Section $section): array
    {
        return $this->buildOptionsRecursive($section);
    }

    /**
     * @param array<int, array{id: string, name: string, url: string, encodeName: string}> $result
     *
     * @return array<int, array{id: string, name: string, url: string, encodeName: string}>
     */
    private function buildOptionsRecursive(Section $section, array $result = []): array
    {
        $result[] = $this->assembleSection($section);

        if (null === $section->parent()) {
            return array_reverse($result);
        }

        return $this->buildOptionsRecursive($section->parent(), $result);
    }
}
