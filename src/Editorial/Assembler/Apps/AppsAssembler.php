<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps;

use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use App\Editorial\Assembler\ResponseAssemblerInterface;
use App\Editorial\BodyTransformContext;
use App\Editorial\EditorialAggregate;
use Ec\Editorial\Domain\Model\EditorialBlog;

final readonly class AppsAssembler implements ResponseAssemblerInterface
{
    public const TWITTER_TYPES = [EditorialBlog::EDITORIAL_TYPE];

    public function __construct(
        private EditorialMetadataAssembler $editorialMetadataAssembler,
        private SectionAssembler $sectionAssembler,
        private TagsAssembler $tagsAssembler,
        private SignaturesAssembler $signaturesAssembler,
        private MultimediaAssembler $multimediaAssembler,
        private StandfirstAssembler $standfirstAssembler,
        private RecommendedAssembler $recommendedAssembler,
        private BodyTransformerPipeline $bodyTransformerPipeline,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function assemble(EditorialAggregate $aggregate): array
    {
        $editorial = $aggregate->editorial;
        $section = $aggregate->section;

        $result = $this->editorialMetadataAssembler->assemble($editorial, $section);

        $result['section'] = $this->sectionAssembler->assembleSection($section);
        $result['tags'] = $this->tagsAssembler->assemble($aggregate->tags, $section);
        $result['adsOptions'] = $this->sectionAssembler->assembleOptions($section);
        $result['analiticsOptions'] = $this->sectionAssembler->assembleOptions($section);

        $result['countComments'] = $aggregate->commentCount;
        $result['signatures'] = $aggregate->signatures;

        $context = BodyTransformContext::fromAggregate($aggregate);

        $result['body'] = $this->bodyTransformerPipeline->transformBody(
            $editorial->body(),
            $context
        );

        $result['multimedia'] = $this->assembleMultimedia($aggregate);

        $result['standfirst'] = $this->standfirstAssembler->assemble(
            $editorial->standFirst(),
            $context
        );

        $result['recommendedEditorials'] = $this->recommendedAssembler->assemble(
            $aggregate->recommendedNews,
            $aggregate->recommendedEditorials,
            $aggregate->multimedia,
            $aggregate->multimediaOpening,
        );

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function assembleMultimedia(EditorialAggregate $aggregate): ?array
    {
        $editorial = $aggregate->editorial;

        if (!empty($aggregate->multimediaOpening)) {
            return $this->multimediaAssembler->assembleOpening(
                $aggregate->multimediaOpening,
                $editorial->opening()
            );
        }

        if (!empty($aggregate->multimedia)) {
            return $this->multimediaAssembler->assembleEditorial(
                $aggregate->multimedia,
                $editorial->multimedia()
            );
        }

        return null;
    }
}
