<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use App\Editorial\Assembler\Apps\StandfirstAssembler;
use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\GenericList;
use Ec\Editorial\Domain\Model\Standfirst;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(StandfirstAssembler::class)]
final class StandfirstAssemblerTest extends TestCase
{
    private StandfirstAssembler $assembler;
    private MockObject&BodyTransformerPipeline $bodyTransformerPipeline;
    private BodyTransformContext $context;

    protected function setUp(): void
    {
        $this->bodyTransformerPipeline = $this->createMock(BodyTransformerPipeline::class);
        $this->assembler = new StandfirstAssembler($this->bodyTransformerPipeline);
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
    public function assembleShouldReturnEmptyArrayWhenContentIsNull(): void
    {
        $standfirst = $this->createMock(Standfirst::class);
        $standfirst->method('content')->willReturn(null);

        $result = $this->assembler->assemble($standfirst, $this->context);

        static::assertSame([], $result);
    }

    #[Test]
    public function assembleShouldDelegateToBodyTransformerPipeline(): void
    {
        $expected = [
            'type' => 'unorderedlist',
            'items' => [
                [
                    'type' => 'listitem',
                    'content' => 'un bolillo',
                    'links' => [],
                ],
                [
                    'type' => 'listitem',
                    'content' => '#replace0#',
                    'links' => [
                        '#replace0#' => [
                            'type' => 'link',
                            'content' => 'dos bolillos',
                            'url' => 'http://www.google.com',
                            'target' => '_self',
                        ],
                    ],
                ],
            ],
        ];

        $content = $this->createMock(GenericList::class);
        $standfirst = $this->createMock(Standfirst::class);
        $standfirst->method('content')->willReturn($content);

        $this->bodyTransformerPipeline->expects(static::once())
            ->method('transformElement')
            ->with($content, $this->context)
            ->willReturn($expected);

        $result = $this->assembler->assemble($standfirst, $this->context);

        static::assertSame($expected, $result);
    }
}
