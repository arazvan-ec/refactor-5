<?php

declare(strict_types=1);

namespace App\Tests\Editorial\Resolver;

use App\Editorial\Resolver\MembershipPromiseResult;
use App\Editorial\Resolver\MembershipResolver;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Body\MembershipCardButtons;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Editorial\Domain\Model\EditorialId;
use Ec\Membership\Infrastructure\Client\Http\QueryMembershipClient;
use Http\Promise\Promise;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

#[CoversClass(MembershipResolver::class)]
final class MembershipResolverTest extends TestCase
{
    private QueryMembershipClient&MockObject $queryMembershipClient;
    private UriFactoryInterface&MockObject $uriFactory;
    private MembershipResolver $resolver;

    protected function setUp(): void
    {
        $this->queryMembershipClient = $this->createMock(QueryMembershipClient::class);
        $this->uriFactory = $this->createMock(UriFactoryInterface::class);
        $this->resolver = new MembershipResolver($this->queryMembershipClient, $this->uriFactory);
    }

    #[Test]
    public function startPromiseShouldReturnPromiseAndLinks(): void
    {
        $editorialId = 'editorial-123';
        $url1 = 'https://example.com/membership1';
        $url2 = 'https://example.com/membership2';

        $editorialIdMock = $this->createMock(EditorialId::class);
        $editorialIdMock->method('id')->willReturn($editorialId);

        $buttonMock = $this->createMock(MembershipCardButton::class);
        $buttonMock->method('urlMembership')->willReturn($url1);
        $buttonMock->method('url')->willReturn($url2);

        $buttonsMock = $this->createMock(MembershipCardButtons::class);
        $buttonsMock->method('buttons')->willReturn([$buttonMock]);

        $membershipCardMock = $this->createMock(BodyTagMembershipCard::class);
        $membershipCardMock->method('buttons')->willReturn($buttonsMock);

        $bodyMock = $this->createMock(Body::class);
        $bodyMock->method('bodyElementsOf')
            ->with(BodyTagMembershipCard::class)
            ->willReturn([$membershipCardMock]);

        $editorialMock = $this->createMock(Editorial::class);
        $editorialMock->method('id')->willReturn($editorialIdMock);
        $editorialMock->method('body')->willReturn($bodyMock);

        $uriMock = $this->createMock(UriInterface::class);
        $this->uriFactory->method('createUri')->willReturn($uriMock);

        $promiseMock = $this->createMock(Promise::class);
        $this->queryMembershipClient
            ->expects(static::once())
            ->method('getMembershipUrl')
            ->willReturn($promiseMock);

        $result = $this->resolver->startPromise($editorialMock, '1');

        static::assertInstanceOf(MembershipPromiseResult::class, $result);
        static::assertSame($promiseMock, $result->promise);
        static::assertSame([$url1, $url2], $result->links);
    }

    #[Test]
    public function resolveShouldReturnCombinedLinksOnSuccess(): void
    {
        $links = ['link1', 'link2'];
        $resolvedValues = ['resolved1', 'resolved2'];

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock
            ->expects(static::once())
            ->method('wait')
            ->willReturn($resolvedValues);

        $result = $this->resolver->resolve($promiseMock, $links);

        static::assertSame(['link1' => 'resolved1', 'link2' => 'resolved2'], $result);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayOnException(): void
    {
        $promiseMock = $this->createMock(Promise::class);
        $promiseMock
            ->expects(static::once())
            ->method('wait')
            ->willThrowException(new \Exception('Promise failed'));

        $result = $this->resolver->resolve($promiseMock, ['link1']);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayWhenPromiseIsNull(): void
    {
        $result = $this->resolver->resolve(null, []);

        static::assertSame([], $result);
    }

    #[Test]
    public function resolveShouldReturnEmptyArrayWhenPromiseReturnsEmpty(): void
    {
        $promiseMock = $this->createMock(Promise::class);
        $promiseMock->method('wait')->willReturn([]);

        $result = $this->resolver->resolve($promiseMock, ['link1']);

        static::assertSame([], $result);
    }
}
