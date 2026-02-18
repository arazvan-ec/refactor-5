<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use App\Infrastructure\Service\PictureShots;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagPicture;

final readonly class BodyTagPictureTransformer implements BodyElementTransformer
{
    public function __construct(
        private PictureShots $pictureShots,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(BodyElement $element, BodyTransformContext $context): array
    {
        \assert($element instanceof BodyTagPicture);

        $elementArray = [
            'type' => $element->type(),
        ];

        $shots = $this->pictureShots->retrieveShotsByPhotoId(
            ['photoFromBodyTags' => $context->bodyPhotos],
            $element,
        );

        if (\count($shots)) {
            $elementArray['shots'] = $shots;
            $elementArray['url'] = reset($shots);
            $elementArray['caption'] = $element->caption() ?: $element->alternate();
            $elementArray['alternate'] = $element->alternate();
            $elementArray['orientation'] = $element->orientation();
        }

        return $elementArray;
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return BodyTagPicture::class;
    }
}
