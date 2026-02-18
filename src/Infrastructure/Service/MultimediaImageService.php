<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Ec\Editorial\Domain\Model\Multimedia\Multimedia;
use Ec\Editorial\Domain\Model\Multimedia\MultimediaId;
use Ec\Editorial\Domain\Model\Multimedia\PhotoExist;
use Ec\Editorial\Domain\Model\Multimedia\Video;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia as MultimediaModel;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;

final readonly class MultimediaImageService
{
    /**
     * @var array<string, array<string, string>>
     */
    public const SIZES = [
        '202w' => [
            'width' => '202',
            'height' => '152',
        ],
        '144w' => [
            'width' => '144',
            'height' => '108',
        ],
        '128w' => [
            'width' => '128',
            'height' => '96',
        ],
    ];

    public function __construct(
        private Thumbor $thumbor,
    ) {
    }

    public function getMultimediaId(Multimedia $multimedia): ?MultimediaId
    {
        if ($multimedia instanceof PhotoExist) {
            return $multimedia->id();
        }

        if (
            ($multimedia instanceof Video || $multimedia instanceof Widget)
            && ($multimedia->photo() instanceof PhotoExist)
        ) {
            return $multimedia->photo()->id();
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    public function getShotsLandscape(MultimediaModel $multimedia): array
    {
        $shots = [];
        $clippings = $multimedia->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_ARTICLE_4_3);

        foreach (self::SIZES as $type => $size) {
            $shots[$type] = $this->thumbor->retriveCropBodyTagPicture(
                $multimedia->file(),
                $size['width'],
                $size['height'],
                $clipping->topLeftX(),
                $clipping->topLeftY(),
                $clipping->bottomRightX(),
                $clipping->bottomRightY()
            );
        }

        return $shots;
    }

    /**
     * @param array{opening: MultimediaPhoto, resource: Photo} $multimediaOpening
     *
     * @return array<string, string>
     */
    public function getShotsLandscapeFromMedia(array $multimediaOpening): array
    {
        $shots = [];
        $clippings = $multimediaOpening['opening']->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_ARTICLE_4_3);

        foreach (self::SIZES as $type => $size) {
            $shots[$type] = $this->thumbor->retriveCropBodyTagPicture(
                $multimediaOpening['resource']->file(),
                $size['width'],
                $size['height'],
                $clipping->topLeftX(),
                $clipping->topLeftY(),
                $clipping->bottomRightX(),
                $clipping->bottomRightY()
            );
        }

        return $shots;
    }
}
