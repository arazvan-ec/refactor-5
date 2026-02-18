<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\SignaturesAssembler;
use App\Infrastructure\Service\Thumbor;
use App\Infrastructure\Service\UrlGenerator;
use Ec\Journalist\Domain\Model\Alias;
use Ec\Journalist\Domain\Model\AliasId;
use Ec\Journalist\Domain\Model\Aliases;
use Ec\Journalist\Domain\Model\Department;
use Ec\Journalist\Domain\Model\DepartmentId;
use Ec\Journalist\Domain\Model\Departments;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistId;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(SignaturesAssembler::class)]
final class SignaturesAssemblerTest extends TestCase
{
    private SignaturesAssembler $assembler;
    private MockObject&Thumbor $thumbor;

    protected function setUp(): void
    {
        $this->thumbor = $this->createMock(Thumbor::class);
        $this->assembler = new SignaturesAssembler(
            new UrlGenerator('dev'),
            $this->thumbor
        );
    }

    #[Test]
    public function assembleShouldReturnEmptyArrayWhenNoMatchingAlias(): void
    {
        $journalistMock = $this->createMock(Journalist::class);
        $aliasesMock = $this->createMock(Aliases::class);
        $journalistMock->method('aliases')->willReturn($aliasesMock);

        $sectionMock = $this->createMock(Section::class);

        $result = $this->assembler->assemble('non-matching-id', $journalistMock, $sectionMock, false);

        static::assertSame([], $result);
    }

    #[Test]
    public function assembleShouldReturnJournalistDataForMatchingAlias(): void
    {
        $aliasId = '20116';
        $journalistId = '5164';
        $journalistName = 'Juan Carlos';
        $photoUrl = 'photo.jpg';
        $expectedThumbor = 'https://thumbor.example.com/photo.jpg';

        $journalistIdMock = $this->createMock(JournalistId::class);
        $journalistIdMock->method('id')->willReturn($journalistId);

        $aliasIdMock = $this->createMock(AliasId::class);
        $aliasIdMock->method('id')->willReturn($aliasId);

        $aliasMock = $this->createMock(Alias::class);
        $aliasMock->method('id')->willReturn($aliasIdMock);
        $aliasMock->method('name')->willReturn($journalistName);
        $aliasMock->method('private')->willReturn(false);

        $departmentsMock = $this->createMock(Departments::class);

        $aliasesMock = $this->createMock(Aliases::class);
        $this->configureIterator($aliasesMock, [$aliasMock]);

        $journalistMock = $this->createMock(Journalist::class);
        $journalistMock->method('id')->willReturn($journalistIdMock);
        $journalistMock->method('aliases')->willReturn($aliasesMock);
        $journalistMock->method('isVisible')->willReturn(true);
        $journalistMock->method('name')->willReturn($journalistName);
        $journalistMock->method('departments')->willReturn($departmentsMock);
        $journalistMock->method('blogPhoto')->willReturn('');
        $journalistMock->method('photo')->willReturn($photoUrl);

        $this->thumbor->method('createJournalistImage')
            ->with($photoUrl)
            ->willReturn($expectedThumbor);

        $sectionMock = $this->createMock(Section::class);
        $sectionMock->method('siteId')->willReturn('1');

        $result = $this->assembler->assemble($aliasId, $journalistMock, $sectionMock, false);

        static::assertSame($journalistId, $result['journalistId']);
        static::assertSame($aliasId, $result['aliasId']);
        static::assertSame($journalistName, $result['name']);
        static::assertFalse($result['private']);
        static::assertStringContainsString('autores', $result['url']);
        static::assertSame($expectedThumbor, $result['photo']);
        static::assertSame([], $result['departments']);
        static::assertArrayNotHasKey('twitter', $result);
    }

    #[Test]
    public function assembleShouldReturnEmptyUrlWhenNotVisible(): void
    {
        $aliasId = '20116';

        $journalistIdMock = $this->createMock(JournalistId::class);
        $journalistIdMock->method('id')->willReturn('5164');

        $aliasIdMock = $this->createMock(AliasId::class);
        $aliasIdMock->method('id')->willReturn($aliasId);

        $aliasMock = $this->createMock(Alias::class);
        $aliasMock->method('id')->willReturn($aliasIdMock);
        $aliasMock->method('name')->willReturn('Name');
        $aliasMock->method('private')->willReturn(false);

        $departmentsMock = $this->createMock(Departments::class);

        $aliasesMock = $this->createMock(Aliases::class);
        $this->configureIterator($aliasesMock, [$aliasMock]);

        $journalistMock = $this->createMock(Journalist::class);
        $journalistMock->method('id')->willReturn($journalistIdMock);
        $journalistMock->method('aliases')->willReturn($aliasesMock);
        $journalistMock->method('isVisible')->willReturn(false);
        $journalistMock->method('departments')->willReturn($departmentsMock);
        $journalistMock->method('blogPhoto')->willReturn('');
        $journalistMock->method('photo')->willReturn('');

        $sectionMock = $this->createMock(Section::class);

        $result = $this->assembler->assemble($aliasId, $journalistMock, $sectionMock, false);

        static::assertSame('', $result['url']);
    }

    #[Test]
    public function assembleShouldIncludeTwitterWithAtPrefixWhenEnabled(): void
    {
        $aliasId = '20116';

        $journalistIdMock = $this->createMock(JournalistId::class);
        $journalistIdMock->method('id')->willReturn('5164');

        $aliasIdMock = $this->createMock(AliasId::class);
        $aliasIdMock->method('id')->willReturn($aliasId);

        $aliasMock = $this->createMock(Alias::class);
        $aliasMock->method('id')->willReturn($aliasIdMock);
        $aliasMock->method('name')->willReturn('Name');
        $aliasMock->method('private')->willReturn(false);

        $departmentsMock = $this->createMock(Departments::class);

        $aliasesMock = $this->createMock(Aliases::class);
        $this->configureIterator($aliasesMock, [$aliasMock]);

        $journalistMock = $this->createMock(Journalist::class);
        $journalistMock->method('id')->willReturn($journalistIdMock);
        $journalistMock->method('aliases')->willReturn($aliasesMock);
        $journalistMock->method('isVisible')->willReturn(false);
        $journalistMock->method('departments')->willReturn($departmentsMock);
        $journalistMock->method('blogPhoto')->willReturn('');
        $journalistMock->method('photo')->willReturn('');
        $journalistMock->method('twitter')->willReturn('elconfidencial');

        $sectionMock = $this->createMock(Section::class);

        $result = $this->assembler->assemble($aliasId, $journalistMock, $sectionMock, true);

        static::assertSame('@elconfidencial', $result['twitter']);
    }

    #[Test]
    public function assembleShouldUseBlogPhotoWhenAvailable(): void
    {
        $aliasId = '20116';
        $blogPhoto = 'blog-photo.jpg';
        $expectedThumbor = 'https://thumbor.example.com/blog-photo.jpg';

        $journalistIdMock = $this->createMock(JournalistId::class);
        $journalistIdMock->method('id')->willReturn('5164');

        $aliasIdMock = $this->createMock(AliasId::class);
        $aliasIdMock->method('id')->willReturn($aliasId);

        $aliasMock = $this->createMock(Alias::class);
        $aliasMock->method('id')->willReturn($aliasIdMock);
        $aliasMock->method('name')->willReturn('Name');
        $aliasMock->method('private')->willReturn(false);

        $departmentsMock = $this->createMock(Departments::class);

        $aliasesMock = $this->createMock(Aliases::class);
        $this->configureIterator($aliasesMock, [$aliasMock]);

        $journalistMock = $this->createMock(Journalist::class);
        $journalistMock->method('id')->willReturn($journalistIdMock);
        $journalistMock->method('aliases')->willReturn($aliasesMock);
        $journalistMock->method('isVisible')->willReturn(false);
        $journalistMock->method('departments')->willReturn($departmentsMock);
        $journalistMock->method('blogPhoto')->willReturn($blogPhoto);

        $this->thumbor->method('createJournalistImage')
            ->with($blogPhoto)
            ->willReturn($expectedThumbor);

        $sectionMock = $this->createMock(Section::class);

        $result = $this->assembler->assemble($aliasId, $journalistMock, $sectionMock, false);

        static::assertSame($expectedThumbor, $result['photo']);
    }

    #[Test]
    public function assembleShouldIncludeDepartments(): void
    {
        $aliasId = '20116';

        $journalistIdMock = $this->createMock(JournalistId::class);
        $journalistIdMock->method('id')->willReturn('5164');

        $aliasIdMock = $this->createMock(AliasId::class);
        $aliasIdMock->method('id')->willReturn($aliasId);

        $aliasMock = $this->createMock(Alias::class);
        $aliasMock->method('id')->willReturn($aliasIdMock);
        $aliasMock->method('name')->willReturn('Name');
        $aliasMock->method('private')->willReturn(false);

        $departmentIdMock = $this->createMock(DepartmentId::class);
        $departmentIdMock->method('id')->willReturn('dept-1');

        $departmentMock = $this->createMock(Department::class);
        $departmentMock->method('id')->willReturn($departmentIdMock);
        $departmentMock->method('name')->willReturn('Tech');

        $departmentsMock = $this->createMock(Departments::class);
        $this->configureIterator($departmentsMock, [$departmentMock]);

        $aliasesMock = $this->createMock(Aliases::class);
        $this->configureIterator($aliasesMock, [$aliasMock]);

        $journalistMock = $this->createMock(Journalist::class);
        $journalistMock->method('id')->willReturn($journalistIdMock);
        $journalistMock->method('aliases')->willReturn($aliasesMock);
        $journalistMock->method('isVisible')->willReturn(false);
        $journalistMock->method('departments')->willReturn($departmentsMock);
        $journalistMock->method('blogPhoto')->willReturn('');
        $journalistMock->method('photo')->willReturn('');

        $sectionMock = $this->createMock(Section::class);

        $result = $this->assembler->assemble($aliasId, $journalistMock, $sectionMock, false);

        static::assertCount(1, $result['departments']);
        static::assertSame('dept-1', $result['departments'][0]['id']);
        static::assertSame('Tech', $result['departments'][0]['name']);
    }

    /**
     * @param array<object> $items
     */
    private function configureIterator(MockObject $mock, array $items): void
    {
        $iterator = new \ArrayIterator($items);

        $mock->method('rewind')->willReturnCallback(
            static function () use ($iterator): void {
                $iterator->rewind();
            }
        );
        $mock->method('current')->willReturnCallback(
            static function () use ($iterator) {
                return $iterator->current();
            }
        );
        $mock->method('key')->willReturnCallback(
            static function () use ($iterator) {
                return $iterator->key();
            }
        );
        $mock->method('next')->willReturnCallback(
            static function () use ($iterator): void {
                $iterator->next();
            }
        );
        $mock->method('valid')->willReturnCallback(
            static function () use ($iterator): bool {
                return $iterator->valid();
            }
        );
    }
}
