<?php

declare(strict_types=1);

namespace App\Editorial\Resolver;

use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;
use Ec\Multimedia\Domain\Model\Photo\Photo;
use Ec\Multimedia\Infrastructure\Client\Http\QueryMultimediaClient;
use Psr\Log\LoggerInterface;

final readonly class BodyPhotosResolver
{
    public function __construct(
        private QueryMultimediaClient $queryMultimediaClient,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Extracts photo IDs from body elements and fetches the corresponding photos.
     *
     * @return array<string, Photo>
     */
    public function resolve(Body $body): array
    {
        $result = [];

        /** @var BodyTagPicture[] $bodyTagPictures */
        $bodyTagPictures = $body->bodyElementsOf(BodyTagPicture::class);
        foreach ($bodyTagPictures as $bodyTagPicture) {
            $result = $this->addPhotoToArray($bodyTagPicture->id()->id(), $result);
        }

        /** @var BodyTagMembershipCard[] $bodyTagMembershipCards */
        $bodyTagMembershipCards = $body->bodyElementsOf(BodyTagMembershipCard::class);
        foreach ($bodyTagMembershipCards as $bodyTagMembershipCard) {
            $id = $bodyTagMembershipCard->bodyTagPictureMembership()->id()->id();
            $result = $this->addPhotoToArray($id, $result);
        }

        return $result;
    }

    /**
     * @param array<string, Photo> $result
     *
     * @return array<string, Photo>
     */
    private function addPhotoToArray(string $id, array $result): array
    {
        try {
            $photo = $this->queryMultimediaClient->findPhotoById($id);
            $result[$id] = $photo;
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());
        }

        return $result;
    }
}
