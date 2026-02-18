<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps;

use App\Infrastructure\Service\Thumbor;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Encode\Encode;
use Ec\Journalist\Domain\Model\Alias;
use Ec\Journalist\Domain\Model\Department;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Section\Domain\Model\Section;

final readonly class SignaturesAssembler
{
    private const TWITTER_REGEX = '/^([A-Za-z0-9_]{1,15})$/';

    public function __construct(
        private UrlGenerator $urlGenerator,
        private Thumbor $thumbor,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assemble(string $aliasId, Journalist $journalist, Section $section, bool $hasTwitter): array
    {
        $signature = [];

        /** @var Alias $alias */
        foreach ($journalist->aliases() as $alias) {
            if ($alias->id()->id() === $aliasId) {
                $signature['journalistId'] = $journalist->id()->id();
                $signature['aliasId'] = $alias->id()->id();
                $signature['name'] = $alias->name();
                $signature['private'] = $alias->private();
                $signature['url'] = '';

                if ($journalist->isVisible()) {
                    $signature['url'] = $this->buildJournalistUrl($journalist, $section);
                }

                $signature['photo'] = $this->buildPhotoUrl($journalist);

                $departments = [];
                /** @var Department $department */
                foreach ($journalist->departments() as $department) {
                    $departments[] = [
                        'id' => $department->id()->id(),
                        'name' => $department->name(),
                    ];
                }

                $signature['departments'] = $departments;

                if ($hasTwitter && !empty($journalist->twitter())) {
                    $signature['twitter'] = $this->withAt($journalist->twitter());
                }
            }
        }

        return $signature;
    }

    private function buildJournalistUrl(Journalist $journalist, Section $section): string
    {
        return $this->urlGenerator->generateUrl(
            'https://%s.%s.%s/autores/%s/',
            'www',
            $section->siteId(),
            \sprintf('%s-%s', Encode::encodeUrl($journalist->name()), $journalist->id()->id())
        );
    }

    private function buildPhotoUrl(Journalist $journalist): string
    {
        if (!empty($journalist->blogPhoto())) {
            return $this->thumbor->createJournalistImage($journalist->blogPhoto());
        }

        if (!empty($journalist->photo())) {
            return $this->thumbor->createJournalistImage($journalist->photo());
        }

        return '';
    }

    private function withAt(string $twitter): string
    {
        if (preg_match(self::TWITTER_REGEX, $twitter)) {
            return \sprintf('@%s', $twitter);
        }

        return $twitter;
    }
}
