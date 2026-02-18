<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\UnorderedListTransformer;
use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\LinkExtractor;
use App\Tests\ArrayIteratorTrait;
use Ec\Editorial\Domain\Model\Body\Link;
use Ec\Editorial\Domain\Model\Body\ListItem;
use Ec\Editorial\Domain\Model\Body\UnorderedList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnorderedListTransformer::class)]
final class UnorderedListTransformerTest extends TestCase
{
    use ArrayIteratorTrait;

    private UnorderedListTransformer $transformer;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->transformer = new UnorderedListTransformer(new LinkExtractor());
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
    public function supportsShouldReturnUnorderedListClass(): void
    {
        static::assertSame(UnorderedList::class, $this->transformer->supports());
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
        $expectedListItem = [
            'type' => 'listitem',
            'content' => 'List item con #replace0#',
            'links' => [$linkMock],
        ];

        $listItemMock = $this->createConfiguredMock(ListItem::class, $expectedListItem);

        $bodyElementMock = $this->createConfiguredMock(UnorderedList::class, [
            'type' => 'unorderedlist',
        ]);
        $this->configureArrayIteratorMock($listItemMock, $bodyElementMock);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'unorderedlist',
            'items' => [
                [
                    'type' => 'listitem',
                    'content' => 'List item con #replace0#',
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
            'content' => 'List item con links',
            'links' => [],
        ]);

        $bodyElementMock = $this->createConfiguredMock(UnorderedList::class, [
            'type' => 'unorderedlist',
        ]);
        $this->configureArrayIteratorMock($listItemMock, $bodyElementMock);

        $result = $this->transformer->transform($bodyElementMock, $this->context);

        static::assertSame([
            'type' => 'unorderedlist',
            'items' => [
                [
                    'type' => 'listitem',
                    'content' => 'List item con links',
                    'links' => null,
                ],
            ],
        ], $result);
    }
}
