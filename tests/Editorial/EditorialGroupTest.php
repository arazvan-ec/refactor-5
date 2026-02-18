<?php

declare(strict_types=1);

namespace App\Tests\Editorial;

use App\Editorial\EditorialGroup;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(EditorialGroup::class)]
final class EditorialGroupTest extends TestCase
{
    #[Test]
    public function constructShouldStoreAllProperties(): void
    {
        $editorialMock = $this->createMock(Editorial::class);
        $sectionMock = $this->createMock(Section::class);
        $signatures = [['journalistId' => '1', 'name' => 'Test']];
        $multimediaId = 'multimedia-123';

        $group = new EditorialGroup(
            editorial: $editorialMock,
            section: $sectionMock,
            signatures: $signatures,
            multimediaId: $multimediaId,
        );

        static::assertSame($editorialMock, $group->editorial);
        static::assertSame($sectionMock, $group->section);
        static::assertSame($signatures, $group->signatures);
        static::assertSame($multimediaId, $group->multimediaId);
    }
}
