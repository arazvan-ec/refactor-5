<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\NumberedListTransformer;
use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\LinkExtractor;
use App\Tests\ArrayIteratorTrait;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\ListItem;
use Ec\Editorial\Domain\Model\Body\NumberedList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(NumberedListTransformer::class)]
final class NumberedListTransformerTest extends TestCase
{
    use ArrayIteratorTrait;

    private NumberedListTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new NumberedListTransformer(new LinkExtractor());
        $this->context = new BodyTransformContext(
            multimedia: [],
            multimediaOpening: [],
            insertedNews: [],
            recommendedEditorials: [],
            bodyPhotos: [],
            membershipLinks: [],
        );
    }

    #[Test]
    public function supportsShouldReturnNumberedListClass(): void
    {
        static::assertSame(NumberedList::class, $this->transformer->supports());
    }

    #[Test]
    public function transformShouldReturnExpectedArrayWithLinks(): void
    {
        $expectedLink = [
            'type' => 'link',
            'content' => 'link',
            'url' => 'https://www.elconfidencial.com/',
            'target' => '_self',
        ];

        $linkMock = $this->createConfiguredMock(Link::class, $expectedLink);
        $listItemMock = $this->createConfiguredMock(ListItem::class, [
            'type' => 'listitem',
            'content' => 'List item con links',
            'links' => [$linkMock],
        ]);

        $bodyElementMock = $this->createConfiguredMock(NumberedList::class, [
            'type' => 'numberedlist',
        ]);
        $this->configureArrayIteratorMock($listItemMock, $bodyElementMock);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'numberedlist',
            'items' => [
                [
                    'type' => 'listitem',
                    'content' => 'List item con links',
                    'links' => [$expectedLink],
                ],
            ],
        ], $result);
    }

    #[Test]
    public function transformShouldReturnNullLinksWhenEmpty(): void
    {
        $listItemMock = $this->createConfiguredMock(ListItem::class, [
            'type' => 'listitem',
            'content' => 'List item con #replace0#',
            'links' => [],
        ]);

        $bodyElementMock = $this->createConfiguredMock(NumberedList::class, [
            'type' => 'numberedlist',
        ]);
        $this->configureArrayIteratorMock($listItemMock, $bodyElementMock);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'numberedlist',
            'items' => [
                [
                    'type' => 'listitem',
                    'content' => 'List item con #replace0#',
                    'links' => null,
                ],
            ],
        ], $result);
    }
}
