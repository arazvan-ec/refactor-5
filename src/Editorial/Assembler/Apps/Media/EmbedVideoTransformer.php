<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Media;

use Ec\Editorial\Domain\Model\Opening;
use Ec\Multimedia\Domain\Model\Multimedia\MultimediaEmbedVideo;

final readonly class EmbedVideoTransformer implements MediaTransformer
{
    private const string EMBED_VIDEO_GENERIC = 'embedVideo';
    private const string EMBED_VIDEO_DAILY_MOTION = 'embedVideoDailyMotion';
    private const string REGEX_PATTERN = '/\/player\/([a-zA-Z0-9]+)\.html\?video=([a-zA-Z0-9]+)/';
    private const int PLAYER_ID_POSITION = 1;
    private const int VIDEO_ID_POSITION = 2;

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

        /** @var MultimediaEmbedVideo $multimedia */
        $multimedia = $multimediaData[$multimediaId]['opening'];

        return $this->isDailyMotionVideo($multimedia)
            ? $this->buildDailyMotionResponse($multimediaId, $multimedia)
            : $this->buildGenericResponse($multimediaId, $multimedia);
    }

    /**
     * @return class-string
     */
    public function supports(): string
    {
        return MultimediaEmbedVideo::class;
    }

    private function isDailyMotionVideo(MultimediaEmbedVideo $multimedia): bool
    {
        return str_contains($multimedia->html(), 'dailymotion.com');
    }

    /**
     * @return array{id: string, type: string, caption: string, playerId: string, videoId: string}|array{}
     */
    private function buildDailyMotionResponse(string $multimediaId, MultimediaEmbedVideo $multimedia): array
    {
        $dailyMotionData = $this->extractDailyMotionData($multimedia);

        if (empty($dailyMotionData)) {
            return [];
        }

        return [
            'id' => $multimediaId,
            'type' => self::EMBED_VIDEO_DAILY_MOTION,
            'caption' => $multimedia->caption(),
            'playerId' => $dailyMotionData['playerId'],
            'videoId' => $dailyMotionData['videoId'],
        ];
    }

    /**
     * @return array{id: string, type: string, caption: string, html: string}
     */
    private function buildGenericResponse(string $multimediaId, MultimediaEmbedVideo $multimedia): array
    {
        return [
            'id' => $multimediaId,
            'type' => self::EMBED_VIDEO_GENERIC,
            'caption' => $multimedia->caption(),
            'html' => $multimedia->html(),
        ];
    }

    /**
     * @return array{playerId: string, videoId: string}|array{}
     */
    private function extractDailyMotionData(MultimediaEmbedVideo $multimedia): array
    {
        $htmlContent = $multimedia->html();

        $matches = [];
        @preg_match(self::REGEX_PATTERN, $htmlContent, $matches);

        if (!isset($matches[self::PLAYER_ID_POSITION], $matches[self::VIDEO_ID_POSITION])) {
            return [];
        }

        return [
            'playerId' => $matches[self::PLAYER_ID_POSITION],
            'videoId' => $matches[self::VIDEO_ID_POSITION],
        ];
    }
}
