<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\BodyPhotosResolver;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Editorial\Domain\Model\Body\BodyTagPictureMembership;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(BodyPhotosResolver::class)]
final class BodyPhotosResolverTest extends TestCase
{
    private QueryMultimediaClient&MockObject $queryMultimediaClient;
    private LoggerInterface&MockObject $logger;
    private BodyPhotosResolver $resolver;

    protected function setUp(): void
    {
        $this->queryMultimediaClient = $this->createMock(QueryMultimediaClient::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resolver = new BodyPhotosResolver($this->queryMultimediaClient, $this->logger);
    }

    #[Test]
    public function resolveShouldReturnPhotosFromBodyTagPictures(): void
    {
        $photoId = 'photo-123';
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn($photoId);

        $bodyTagPictureMock = $this->createMock(BodyTagPicture::class);
        $bodyTagPictureMock->method('id')->willReturn($multimediaIdMock);

        $photoMock = $this->createMock(Photo::class);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock
            ->expects(static::exactly(2))
            ->method('bodyElementsOf')
            ->willReturnOnConsecutiveCalls(
                [$bodyTagPictureMock],
                [],
            );

        $this->queryMultimediaClient
            ->expects(static::once())
            ->method('findPhotoById')
            ->with($photoId)
            ->willReturn($photoMock);

        $result = $this->resolver->resolve($bodyMock);

        static::assertCount(1, $result);
        static::assertSame($photoMock, $result[$photoId]);
    }

    #[Test]
    public function resolveShouldReturnPhotosFromMembershipCards(): void
    {
        $photoId = 'membership-photo-456';
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn($photoId);

        $pictureMembershipMock = $this->createMock(BodyTagPictureMembership::class);
        $pictureMembershipMock->method('id')->willReturn($multimediaIdMock);

        $membershipCardMock = $this->createMock(BodyTagMembershipCard::class);
        $membershipCardMock->method('bodyTagPictureMembership')->willReturn($pictureMembershipMock);

        $photoMock = $this->createMock(Photo::class);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock
            ->expects(static::exactly(2))
            ->method('bodyElementsOf')
            ->willReturnOnConsecutiveCalls(
                [],
                [$membershipCardMock],
            );

        $this->queryMultimediaClient
            ->expects(static::once())
            ->method('findPhotoById')
            ->with($photoId)
            ->willReturn($photoMock);

        $result = $this->resolver->resolve($bodyMock);

        static::assertCount(1, $result);
        static::assertSame($photoMock, $result[$photoId]);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayWhenNoBodyElements(): void
    {
        $bodyMock = $this->createMock(Body::class);
        $bodyMock->method('bodyElementsOf')->willReturn([]);

        $result = $this->resolver->resolve($bodyMock);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldLogErrorAndContinueOnException(): void
    {
        $photoId = 'photo-error';
        $multimediaIdMock = $this->createMock(MultimediaId::class);
        $multimediaIdMock->method('id')->willReturn($photoId);

        $bodyTagPictureMock = $this->createMock(BodyTagPicture::class);
        $bodyTagPictureMock->method('id')->willReturn($multimediaIdMock);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock
            ->expects(static::exactly(2))
            ->method('bodyElementsOf')
            ->willReturnOnConsecutiveCalls(
                [$bodyTagPictureMock],
                [],
            );

        $this->queryMultimediaClient
            ->expects(static::once())
            ->method('findPhotoById')
            ->willThrowException(new \Exception('Photo not found'));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with('Photo not found');

        $result = $this->resolver->resolve($bodyMock);

        static::assertSame([], $result);
    }
}
