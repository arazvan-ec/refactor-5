<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps\Body;

use App\Editorial\Assembler\Apps\Body\BodyTagMembershipCardTransformer;
use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use App\Editorial\BodyTransformContext;
use App\Tests\Editorial\Assembler\Apps\Body\DataProvider\BodyTagMembershipCardDataProvider;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Body\MembershipCardButtons;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyTagMembershipCardTransformer::class)]
final class BodyTagMembershipCardTransformerTest extends TestCase
{
    private BodyTagMembershipCardTransformer $transformer;
    /** @var BodyTransformerPipeline&MockObject */
    private BodyTransformerPipeline $pipeline;

    protected function setUp(): void
    {
        $this->pipeline = $this->createMock(BodyTransformerPipeline::class);
        $this->transformer = new BodyTagMembershipCardTransformer($this->pipeline);
    }

    #[Test]
    public function supportsShouldReturnBodyTagMembershipCardClass(): void
    {
        static::assertSame(BodyTagMembershipCard::class, $this->transformer->supports());
    }

    /**
     * @param array{
     *      btns: array<array{
     *          url: string,
     *          urlMembership: string,
     *          cta: string
     *      }>,
     *      title: string,
     *      titleBanner: string,
     *      classBanner: string
     *  } $bodyTag
     * @param array<string, mixed> $membershipLinks
     * @param array<string, mixed> $expected
     */
    #[DataProviderExternal(BodyTagMembershipCardDataProvider::class, 'getData')]
    #[Test]
    public function transformShouldReturnExpectedArray(array $bodyTag, array $membershipLinks, array $expected): void
    {
        $context = new BodyTransformContext(
            multimedia: [],
            multimediaOpening: [],
            insertedNews: [],
            recommendedEditorials: [],
            bodyPhotos: [],
            membershipLinks: $membershipLinks,
        );

        $arrayBtnMock = [];
        foreach ($bodyTag['btns'] as $btn) {
            $buttonMock = $this->createMock(MembershipCardButton::class);
            $buttonMock->expects(static::once())
                ->method('url')
                ->willReturn($btn['url']);
            $buttonMock->expects(static::once())
                ->method('urlMembership')
                ->willReturn($btn['urlMembership']);
            $buttonMock->expects(static::once())
                ->method('cta')
                ->willReturn($btn['cta']);
            $arrayBtnMock[] = $buttonMock;
        }

        $buttonCollectionMock = $this->createMock(MembershipCardButtons::class);
        $buttonCollectionMock->expects(static::once())
            ->method('buttons')
            ->willReturn($arrayBtnMock);

        $pictureMock = $this->createMock(BodyTagPictureMembership::class);

        $bodyElementMock = $this->createMock(BodyTagMembershipCard::class);
        $bodyElementMock->expects(static::once())
            ->method('type')
            ->willReturn('bodytagmembershipcard');
        $bodyElementMock->expects(static::once())
            ->method('title')
            ->willReturn($bodyTag['title']);
        $bodyElementMock->expects(static::once())
            ->method('buttons')
            ->willReturn($buttonCollectionMock);
        $bodyElementMock->expects(static::once())
            ->method('titleBanner')
            ->willReturn($bodyTag['titleBanner']);
        $bodyElementMock->expects(static::once())
            ->method('classBanner')
            ->willReturn($bodyTag['classBanner']);
        $bodyElementMock->expects(static::once())
            ->method('bodyTagPictureMembership')
            ->willReturn($pictureMock);

        $this->pipeline->expects(static::once())
            ->method('transformElement')
            ->with($pictureMock, $context)
            ->willReturn([]);

        $result = $this->transformer->transform($bodyElementMock, $context);

        static::assertSame($expected, $result);
    }
}
