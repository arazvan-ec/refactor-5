<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Enum\SitesEnum;
use App\Infrastructure\Service\UrlGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlGenerator::class)]
class UrlGeneratorTest extends TestCase
{
    #[Test]
    public function generateUrlShouldReturnCorrectUrlForElconfidencial(): void
    {
        $urlGenerator = new UrlGenerator('dev');

        $result = $urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            'www',
            SitesEnum::ELCONFIDENCIAL->value,
            '/path/to/article'
        );

        $this->assertSame('https://www.elconfidencial.dev/path/to/article', $result);
    }

    #[Test]
    public function generateUrlShouldReturnCorrectUrlForBlog(): void
    {
        $urlGenerator = new UrlGenerator('dev');

        $result = $urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            'blog',
            SitesEnum::ELCONFIDENCIAL->value,
            '/path/to/article'
        );

        $this->assertSame('https://blog.elconfidencial.dev/path/to/article', $result);
    }

    #[Test]
    public function generateUrlShouldTrimLeadingAndTrailingSlashesFromUrlPath(): void
    {
        $urlGenerator = new UrlGenerator('com');

        $result = $urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            'www',
            SitesEnum::ELCONFIDENCIAL->value,
            '/path/to/article/'
        );

        $this->assertSame('https://www.elconfidencial.com/path/to/article', $result);
    }

    #[Test]
    public function generateUrlShouldHandleVanitatisSubdomain(): void
    {
        $urlGenerator = new UrlGenerator('com');

        $result = $urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            'www',
            SitesEnum::VANITATIS->value,
            '/lifestyle/article'
        );

        $this->assertSame('https://www.vanitatis.elconfidencial.com/lifestyle/article', $result);
    }

    #[Test]
    public function generateUrlShouldHandleAlimenteSubdomain(): void
    {
        $urlGenerator = new UrlGenerator('com');

        $result = $urlGenerator->generateUrl(
            'https://%s.%s.%s/%s',
            'www',
            SitesEnum::ALIMENTE->value,
            '/nutrition/article'
        );

        $this->assertSame('https://www.alimente.elconfidencial.com/nutrition/article', $result);
    }

    /**
     * @return array<string, array{extension: string, format: string, subdomain: string, siteId: string, urlPath: string, expected: string}>
     */
    public static function urlDataProvider(): array
    {
        return [
            'elconfidencial with dev extension' => [
                'extension' => 'dev',
                'format' => 'https://%s.%s.%s/%s',
                'subdomain' => 'www',
                'siteId' => SitesEnum::ELCONFIDENCIAL->value,
                'urlPath' => '/espana/andalucia',
                'expected' => 'https://www.elconfidencial.dev/espana/andalucia',
            ],
            'vanitatis with com extension' => [
                'extension' => 'com',
                'format' => 'https://%s.%s.%s/%s',
                'subdomain' => 'www',
                'siteId' => SitesEnum::VANITATIS->value,
                'urlPath' => '/moda/tendencias',
                'expected' => 'https://www.vanitatis.elconfidencial.com/moda/tendencias',
            ],
        ];
    }

    #[DataProvider('urlDataProvider')]
    #[Test]
    public function generateUrlShouldReturnExpectedResult(
        string $extension,
        string $format,
        string $subdomain,
        string $siteId,
        string $urlPath,
        string $expected,
    ): void {
        $urlGenerator = new UrlGenerator($extension);

        $result = $urlGenerator->generateUrl($format, $subdomain, $siteId, $urlPath);

        $this->assertSame($expected, $result);
    }
}
