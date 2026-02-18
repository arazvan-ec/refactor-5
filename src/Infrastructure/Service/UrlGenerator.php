<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use App\Infrastructure\Enum\SitesEnum;

final readonly class UrlGenerator
{
    public function __construct(
        private string $extension,
    ) {
    }

    public function generateUrl(string $format, string $subdomain, string $siteId, string $urlPath): string
    {
        return \sprintf(
            $format,
            $subdomain,
            SitesEnum::getHostnameById($siteId),
            $this->extension,
            trim($urlPath, '/')
        );
    }
}
