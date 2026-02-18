<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps;

use App\Infrastructure\Enum\ClossingModeEnum;
use App\Infrastructure\Enum\EditorialTypesEnum;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Encode\Encode;
use Ec\Section\Domain\Model\Section;

final readonly class EditorialMetadataAssembler
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        private UrlGenerator $urlGenerator,
    ) {
    }

    /**
     * @return array<string, array<string, mixed>|bool|int|string>
     */
    public function assemble(Editorial $editorial, Section $section): array
    {
        $editorialType = EditorialTypesEnum::getNameById($editorial->editorialType());

        return [
            'id' => $editorial->id()->id(),
            'url' => $this->buildEditorialUrl($editorial, $section),
            'titles' => [
                'title' => $editorial->editorialTitles()->title(),
                'preTitle' => $editorial->editorialTitles()->preTitle(),
                'urlTitle' => $editorial->editorialTitles()->urlTitle(),
                'mobileTitle' => $editorial->editorialTitles()->mobileTitle(),
            ],
            'lead' => $editorial->lead(),
            'publicationDate' => $editorial->publicationDate()->format(self::DATE_FORMAT),
            'updatedOn' => $editorial->publicationDate()->format(self::DATE_FORMAT),
            'endOn' => $editorial->endOn()->format(self::DATE_FORMAT),
            'type' => [
                'id' => $editorialType['id'],
                'name' => $editorialType['name'],
            ],
            'indexable' => $editorial->indexed(),
            'deleted' => $editorial->isDeleted(),
            'published' => $editorial->isPublished(),
            'closingModeId' => ClossingModeEnum::getClosingModeById($editorial->closingModeId()),
            'commentable' => $editorial->canComment(),
            'isBrand' => $editorial->isBrand(),
            'isAmazonOnsite' => $editorial->isAmazonOnsite(),
            'contentType' => $editorial->contentType(),
            'canonicalEditorialId' => $editorial->canonicalEditorialId(),
            'urlDate' => $editorial->urlDate()->format(self::DATE_FORMAT),
            'countWords' => $editorial->body()->countWords(),
        ];
    }

    private function buildEditorialUrl(Editorial $editorial, Section $section): string
    {
        $editorialPath = \sprintf(
            '%s/%s/%s_%s',
            $section->getPath(),
            $editorial->publicationDate()->format('Y-m-d'),
            Encode::encodeUrl($editorial->editorialTitles()->urlTitle()),
            $editorial->id()->id()
        );

        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            $section->isSubdomainBlog() ? 'blog' : 'www',
            $section->siteId(),
            $editorialPath
        );
    }
}
