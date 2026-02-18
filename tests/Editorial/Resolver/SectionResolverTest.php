<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\SectionResolver;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\QuerySectionClient;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SectionResolver::class)]
final class SectionResolverTest extends TestCase
{
    #[Test]
    public function resolveShouldReturnSectionForEditorial(): void
    {
        $sectionId = 'section-123';
        $sectionMock = $this->createMock(Section::class);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock
            ->expects(static::once())
            ->method('sectionId')
            ->willReturn($sectionId);

        $querySectionClient = $this->createMock(QuerySectionClient::class);
        $querySectionClient
            ->expects(static::once())
            ->method('findSectionById')
            ->with($sectionId)
            ->willReturn($sectionMock);

        $resolver = new SectionResolver($querySectionClient);

        $result = $resolver->resolve($editorialMock);

        static::assertSame($sectionMock, $result);
    }
}
