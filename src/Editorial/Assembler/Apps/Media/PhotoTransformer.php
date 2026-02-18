<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Media;

use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaPhoto;
use Ec\Multimedia\Domain\Model\Photo\Photo;

final readonly class PhotoTransformer implements MediaTransformer
{
    private const string WIDTH = 'width';
    private const string HEIGHT = 'height';

    private const string ASPECT_RATIO_16_9 = '16:9';
    private const string ASPECT_RATIO_3_4 = '3:4';
    private const string ASPECT_RATIO_4_3 = '4:3';
    private const string ASPECT_RATIO_3_2 = '3:2';
    private const string ASPECT_RATIO_2_3 = '2:3';

    /** @var array<string, array<string, array<string, string>>> */
    private const array SIZES_RELATIONS = [
        self::ASPECT_RATIO_4_3 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1080'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '900'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '747'],
            '557w' => [self::WIDTH => '557', self::HEIGHT => '418'],
            '381w' => [self::WIDTH => '381', self::HEIGHT => '286'],
            '600w' => [self::WIDTH => '600', self::HEIGHT => '450'],
            '414w' => [self::WIDTH => '414', self::HEIGHT => '311'],
            '375w' => [self::WIDTH => '375', self::HEIGHT => '281'],
            '360w' => [self::WIDTH => '360', self::HEIGHT => '270'],
            '767w' => [self::WIDTH => '767', self::HEIGHT => '575'],
        ],
        self::ASPECT_RATIO_16_9 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '810'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '675'],
            '972w' => [self::WIDTH => '972', self::HEIGHT => '547'],
            '720w' => [self::WIDTH => '720', self::HEIGHT => '405'],
            '600w' => [self::WIDTH => '600', self::HEIGHT => '338'],
            '414w' => [self::WIDTH => '414', self::HEIGHT => '233'],
            '375w' => [self::WIDTH => '375', self::HEIGHT => '211'],
            '360w' => [self::WIDTH => '360', self::HEIGHT => '203'],
        ],
        self::ASPECT_RATIO_3_4 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '1920'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1600'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '1328'],
            '391w' => [self::WIDTH => '391', self::HEIGHT => '521'],
            '300w' => [self::WIDTH => '300', self::HEIGHT => '400'],
            '600w' => [self::WIDTH => '600', self::HEIGHT => '800'],
            '414w' => [self::WIDTH => '414', self::HEIGHT => '552'],
            '375w' => [self::WIDTH => '375', self::HEIGHT => '500'],
            '360w' => [self::WIDTH => '360', self::HEIGHT => '480'],
        ],
        self::ASPECT_RATIO_3_2 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '960'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '800'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '664'],
            '557w' => [self::WIDTH => '557', self::HEIGHT => '371'],
            '381w' => [self::WIDTH => '381', self::HEIGHT => '254'],
            '600w' => [self::WIDTH => '600', self::HEIGHT => '400'],
            '414w' => [self::WIDTH => '414', self::HEIGHT => '276'],
            '375w' => [self::WIDTH => '375', self::HEIGHT => '250'],
            '360w' => [self::WIDTH => '360', self::HEIGHT => '240'],
            '767w' => [self::WIDTH => '767', self::HEIGHT => '511'],
            'lo-res' => [self::WIDTH => '48', self::HEIGHT => '32'],
        ],
        self::ASPECT_RATIO_2_3 => [
            '1440w' => [self::WIDTH => '1440', self::HEIGHT => '2160'],
            '1200w' => [self::WIDTH => '1200', self::HEIGHT => '1800'],
            '996w' => [self::WIDTH => '996', self::HEIGHT => '1494'],
            '557w' => [self::WIDTH => '557', self::HEIGHT => '835'],
            '381w' => [self::WIDTH => '381', self::HEIGHT => '571'],
            '600w' => [self::WIDTH => '600', self::HEIGHT => '900'],
            '414w' => [self::WIDTH => '414', self::HEIGHT => '621'],
            '375w' => [self::WIDTH => '375', self::HEIGHT => '562'],
            '360w' => [self::WIDTH => '360', self::HEIGHT => '540'],
            '767w' => [self::WIDTH => '767', self::HEIGHT => '1150'],
            'lo-res' => [self::WIDTH => '48', self::HEIGHT => '72'],
        ],
    ];

    public function __construct(
        private Thumbor $thumborService,
    ) {
    }

    /**
     * @param array<string, mixed> $multimediaData
     *
     * @return array<string, mixed>
     */
    public function transform(array $multimediaData, Opening $opening): array
    {
        $multimediaId = $opening->multimediaId();

        if (!$multimediaId || empty($multimediaData[$multimediaId])) {
            return [];
        }

        /** @var MultimediaPhoto $multimedia */
        $multimedia = $multimediaData[$multimediaId]['opening'];
        /** @var Photo $resource */
        $resource = $multimediaData[$multimediaId]['resource'];
        $clippings = $multimedia->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_MULTIMEDIA_BIG);

        $allShots = [];
        foreach (self::SIZES_RELATIONS as $aspectRatio => $sizes) {
            $shots = array_map(
                fn (array $size): string => $this->thumborService->retriveCropBodyTagPicture(
                    $resource->file(),
                    $size[self::WIDTH],
                    $size[self::HEIGHT],
                    $clipping->topLeftX(),
                    $clipping->topLeftY(),
                    $clipping->bottomRightX(),
                    $clipping->bottomRightY()
                ),
                $sizes
            );

            $allShots[$aspectRatio] = $shots;
        }

        return [
            'id' => $multimediaId,
            'type' => 'photo',
            'caption' => $multimedia->caption(),
            'shots' => (object) $allShots,
            'photo' => current($allShots[self::ASPECT_RATIO_16_9]),
        ];
    }

    /**
     * @return class-string
     */
    public function supports(): string
    {
        return MultimediaPhoto::class;
    }
}
