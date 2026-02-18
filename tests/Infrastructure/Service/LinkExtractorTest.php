<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Service;

use App\Infrastructure\Service\LinkExtractor;
use Ec\Editorial\Domain\Model\Body\ElementContentWithLinks;
use Ec\Editorial\Domain\Model\Body\Link;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinkExtractor::class)]
class LinkExtractorTest extends TestCase
{
    private LinkExtractor $linkExtractor;

    protected function setUp(): void
    {
        $this->linkExtractor = new LinkExtractor();
    }

    #[Test]
    public function extractShouldReturnEmptyArrayWhenNoLinks(): void
    {
        $element = $this->createMock(ElementContentWithLinks::class);
        $element->method('links')->willReturn([]);

        $result = $this->linkExtractor->extract($element);

        $this->assertSame([], $result);
    }

    #[Test]
    public function extractShouldReturnFormattedLinksArray(): void
    {
        $link1 = $this->createMock(Link::class);
        $link1->method('type')->willReturn('link');
        $link1->method('content')->willReturn('Click here');
        $link1->method('url')->willReturn('https://example.com');
        $link1->method('target')->willReturn('_blank');

        $link2 = $this->createMock(Link::class);
        $link2->method('type')->willReturn('link');
        $link2->method('content')->willReturn('More info');
        $link2->method('url')->willReturn('https://example.org');
        $link2->method('target')->willReturn('_self');

        $element = $this->createMock(ElementContentWithLinks::class);
        $element->method('links')->willReturn([
            '0' => $link1,
            '1' => $link2,
        ]);

        $result = $this->linkExtractor->extract($element);

        $this->assertCount(2, $result);
        $this->assertSame([
            'type' => 'link',
            'content' => 'Click here',
            'url' => 'https://example.com',
            'target' => '_blank',
        ], $result['0']);
        $this->assertSame([
            'type' => 'link',
            'content' => 'More info',
            'url' => 'https://example.org',
            'target' => '_self',
        ], $result['1']);
    }

    #[Test]
    public function extractShouldPreservePositionKeys(): void
    {
        $link = $this->createMock(Link::class);
        $link->method('type')->willReturn('link');
        $link->method('content')->willReturn('Text');
        $link->method('url')->willReturn('https://example.com');
        $link->method('target')->willReturn('_blank');

        $element = $this->createMock(ElementContentWithLinks::class);
        $element->method('links')->willReturn([
            'pos-5' => $link,
        ]);

        $result = $this->linkExtractor->extract($element);

        $this->assertArrayHasKey('pos-5', $result);
        $this->assertSame('link', $result['pos-5']['type']);
    }
}
