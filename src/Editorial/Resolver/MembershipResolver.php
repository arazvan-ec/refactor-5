<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use App\Infrastructure\Enum\SitesEnum;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Editorial;
use Ec\Membership\Infrastructure\Client\Http\QueryMembershipClient;
use Http\Promise\Promise;
use Psr\Http\Message\UriFactoryInterface;

final readonly class MembershipResolver
{
    public function __construct(
        private QueryMembershipClient $queryMembershipClient,
        private UriFactoryInterface $uriFactory,
    ) {
    }

    /**
     * Starts an async membership URL resolution promise.
     *
     * @return array{promise: Promise|null, links: array<int, string>}
     */
    public function startPromise(Editorial $editorial, string $siteId): array
    {
        $linksData = $this->getLinksFromBody($editorial);

        $links = [];
        $uris = [];

        /** @var string $membershipLink */
        foreach ($linksData as $membershipLink) {
            $uris[] = $this->uriFactory->createUri($membershipLink);
            $links[] = $membershipLink;
        }

        /** @var Promise $promise */
        $promise = $this->queryMembershipClient->getMembershipUrl(
            $editorial->id()->id(),
            $uris,
            SitesEnum::getEncodenameById($siteId),
            true,
        );

        return ['promise' => $promise, 'links' => $links];
    }

    /**
     * Resolves the membership promise and combines with original links.
     *
     * @param array<int, string> $links
     *
     * @return array<string, mixed>
     */
    public function resolve(?Promise $promise, array $links): array
    {
        $membershipLinkResult = [];

        if (null !== $promise) {
            try {
                /** @var array<string, mixed> $membershipLinkResult */
                $membershipLinkResult = $promise->wait();
            } catch (\Throwable) {
                return [];
            }
        }

        if (empty($membershipLinkResult)) {
            return [];
        }

        return array_combine($links, $membershipLinkResult);
    }

    /**
     * Extracts membership links from body membership card buttons.
     *
     * @return array<int, string>
     */
    private function getLinksFromBody(Editorial $editorial): array
    {
        $linksData = [];

        /** @var BodyTagMembershipCard[] $bodyElementsMembership */
        $bodyElementsMembership = $editorial->body()->bodyElementsOf(BodyTagMembershipCard::class);

        /** @var BodyTagMembershipCard $bodyElement */
        foreach ($bodyElementsMembership as $bodyElement) {
            /** @var MembershipCardButton $button */
            foreach ($bodyElement->buttons()->buttons() as $button) {
                $linksData[] = $button->urlMembership();
                $linksData[] = $button->url();
            }
        }

        return $linksData;
    }
}
