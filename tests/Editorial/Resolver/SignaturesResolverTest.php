<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Application\DataTransformer\Apps\JournalistsDataTransformer;
use App\Editorial\Resolver\SignaturesResolver;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialBlog;
use Ec\Editorial\Domain\Model\Signature;
use Ec\Editorial\Domain\Model\SignatureId;
use Ec\Editorial\Domain\Model\Signatures;
use Ec\Journalist\Domain\Model\AliasId;
use Ec\Journalist\Domain\Model\Journalist;
use Ec\Journalist\Domain\Model\JournalistFactory;
use Ec\Journalist\Domain\Model\QueryJournalistClient;
use Ec\Section\Domain\Model\Section;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(SignaturesResolver::class)]
final class SignaturesResolverTest extends TestCase
{
    private QueryJournalistClient&MockObject $queryJournalistClient;
    private JournalistFactory&MockObject $journalistFactory;
    private JournalistsDataTransformer&MockObject $journalistsDataTransformer;
    private LoggerInterface&MockObject $logger;
    private SignaturesResolver $resolver;

    protected function setUp(): void
    {
        $this->queryJournalistClient = $this->createMock(QueryJournalistClient::class);
        $this->journalistFactory = $this->createMock(JournalistFactory::class);
        $this->journalistsDataTransformer = $this->createMock(JournalistsDataTransformer::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->resolver = new SignaturesResolver(
            $this->queryJournalistClient,
            $this->journalistFactory,
            $this->journalistsDataTransformer,
            $this->logger,
        );
    }

    #[Test]
    public function resolveShouldReturnSignaturesForEditorial(): void
    {
        $aliasId = 'alias-1';
        $signatureIdMock = $this->createMock(SignatureId::class);
        $signatureIdMock->method('id')->willReturn($aliasId);

        $signatureMock = $this->createMock(Signature::class);
        $signatureMock->method('id')->willReturn($signatureIdMock);

        $signaturesMock = $this->createMock(Signatures::class);
        $signaturesMock
            ->expects(static::once())
            ->method('getArrayCopy')
            ->willReturn([$signatureMock]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('signatures')->willReturn($signaturesMock);
        $editorialMock->method('editorialType')->willReturn('article');

        $sectionMock = $this->createMock(Section::class);

        $aliasIdModel = $this->createMock(AliasId::class);
        $this->journalistFactory
            ->expects(static::once())
            ->method('buildAliasId')
            ->with($aliasId)
            ->willReturn($aliasIdModel);

        $journalistMock = $this->createMock(Journalist::class);
        $this->queryJournalistClient
            ->expects(static::once())
            ->method('findJournalistByAliasId')
            ->with($aliasIdModel)
            ->willReturn($journalistMock);

        $expectedSignature = ['journalistId' => '1', 'aliasId' => $aliasId, 'name' => 'Test'];
        $this->journalistsDataTransformer
            ->expects(static::once())
            ->method('write')
            ->with($aliasId, $journalistMock, $sectionMock, false)
            ->willReturnSelf();
        $this->journalistsDataTransformer
            ->expects(static::once())
            ->method('read')
            ->willReturn($expectedSignature);

        $result = $this->resolver->resolve($editorialMock, $sectionMock);

        static::assertCount(1, $result);
        static::assertSame($expectedSignature, $result[0]);
    }

    #[Test]
    public function resolveShouldPassHasTwitterTrueForBlogType(): void
    {
        $aliasId = 'alias-1';
        $signatureIdMock = $this->createMock(SignatureId::class);
        $signatureIdMock->method('id')->willReturn($aliasId);

        $signatureMock = $this->createMock(Signature::class);
        $signatureMock->method('id')->willReturn($signatureIdMock);

        $signaturesMock = $this->createMock(Signatures::class);
        $signaturesMock->method('getArrayCopy')->willReturn([$signatureMock]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('signatures')->willReturn($signaturesMock);
        $editorialMock->method('editorialType')->willReturn(EditorialBlog::EDITORIAL_TYPE);

        $sectionMock = $this->createMock(Section::class);

        $aliasIdModel = $this->createMock(AliasId::class);
        $this->journalistFactory->method('buildAliasId')->willReturn($aliasIdModel);

        $journalistMock = $this->createMock(Journalist::class);
        $this->queryJournalistClient->method('findJournalistByAliasId')->willReturn($journalistMock);

        $this->journalistsDataTransformer
            ->expects(static::once())
            ->method('write')
            ->with($aliasId, $journalistMock, $sectionMock, true)
            ->willReturnSelf();
        $this->journalistsDataTransformer->method('read')->willReturn(['data' => 'test']);

        $this->resolver->resolve($editorialMock, $sectionMock);
    }

    #[Test]
    public function resolveShouldSkipEmptyResultsAndLogErrors(): void
    {
        $aliasId = 'alias-1';
        $signatureIdMock = $this->createMock(SignatureId::class);
        $signatureIdMock->method('id')->willReturn($aliasId);

        $signatureMock = $this->createMock(Signature::class);
        $signatureMock->method('id')->willReturn($signatureIdMock);

        $signaturesMock = $this->createMock(Signatures::class);
        $signaturesMock->method('getArrayCopy')->willReturn([$signatureMock]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('signatures')->willReturn($signaturesMock);
        $editorialMock->method('editorialType')->willReturn('article');

        $sectionMock = $this->createMock(Section::class);

        $this->journalistFactory
            ->method('buildAliasId')
            ->willThrowException(new \Exception('Not found'));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with('Not found');

        $result = $this->resolver->resolve($editorialMock, $sectionMock);

        static::assertSame([], $result);
    }

    #[Test]
    public function retrieveAliasFormatShouldReturnEmptyArrayOnException(): void
    {
        $aliasId = 'invalid-alias';
        $sectionMock = $this->createMock(Section::class);

        $this->journalistFactory
            ->method('buildAliasId')
            ->willThrowException(new \Exception('Invalid alias'));

        $this->logger
            ->expects(static::once())
            ->method('error');

        $result = $this->resolver->retrieveAliasFormat($aliasId, $sectionMock);

        static::assertSame([], $result);
    }
}
