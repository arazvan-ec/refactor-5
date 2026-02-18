<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\Media\MediaTransformerPipeline;
use App\Infrastructure\Service\MultimediaImageService;
use App\Infrastructure\Service\Thumbor;
use Ec\Editorial\Domain\Model\Multimedia\Multimedia as MultimediaEditorial;
use Ec\Editorial\Domain\Model\Multimedia\Widget;
use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\ClippingTypes;
use Ec\Multimedia\Domain\Model\Multimedia\Multimedia as AbstractMultimedia;

final readonly class MultimediaAssembler
{
    private const WIDTH = 'width';
    private const HEIGHT = 'height';
    private const ASPECT_RATIO_16_9 = '16:9';
    private const ASPECT_RATIO_3_4 = '3:4';
    private const ASPECT_RATIO_4_3 = '4:3';

    /** @var array<string, array<string, array<string, string>>> */
    private const SIZES_RELATIONS = [
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
    ];

    public function __construct(
        private MediaTransformerPipeline $mediaTransformerPipeline,
        private MultimediaImageService $multimediaImageService,
        private Thumbor $thumbor,
    ) {
    }

    /**
     * Assemble opening multimedia via the media transformer pipeline.
     *
     * @param array<string, array<string, mixed>> $multimediaOpening
     *
     * @return array<string, mixed>|null
     */
    public function assembleOpening(array $multimediaOpening, Opening $opening): ?array
    {
        if (empty($multimediaOpening)) {
            return null;
        }

        $result = $this->mediaTransformerPipeline->transform($multimediaOpening, $opening);

        return empty($result) ? null : $result;
    }

    /**
     * Assemble editorial multimedia (fallback when no opening is available).
     *
     * @param array<string, AbstractMultimedia> $multimedia
     *
     * @return array<string, \stdClass|string>|null
     */
    public function assembleEditorial(array $multimedia, MultimediaEditorial $openingMultimedia): ?array
    {
        if ($openingMultimedia instanceof Widget) {
            return null;
        }

        $multimediaId = $this->multimediaImageService->getMultimediaId($openingMultimedia);

        if (!$multimediaId || empty($multimedia[$multimediaId->id()])) {
            return null;
        }

        /** @var \Ec\Multimedia\Domain\Model\Multimedia $multimediaModel */
        $multimediaModel = $multimedia[$multimediaId->id()];
        $clippings = $multimediaModel->clippings();
        $clipping = $clippings->clippingByType(ClippingTypes::SIZE_MULTIMEDIA_BIG);

        $allShots = [];
        foreach (self::SIZES_RELATIONS as $aspectRatio => $sizes) {
            $shots = array_map(function ($size) use ($clipping, $multimediaModel) {
                return $this->thumbor->retriveCropBodyTagPicture(
                    $multimediaModel->file(),
                    $size[self::WIDTH],
                    $size[self::HEIGHT],
                    $clipping->topLeftX(),
                    $clipping->topLeftY(),
                    $clipping->bottomRightX(),
                    $clipping->bottomRightY()
                );
            }, $sizes);

            $allShots[$aspectRatio] = $shots;
        }

        return [
            'id' => $multimediaModel->id(),
            'type' => 'photo',
            'caption' => $multimediaModel->caption(),
            'shots' => (object) $allShots,
            'photo' => current($allShots[self::ASPECT_RATIO_16_9]),
        ];
    }
}
