<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Domain\Model\Body\BodyTagMembershipCard;
use Ec\Editorial\Domain\Model\Body\MembershipCardButton;
use Ec\Editorial\Domain\Model\Body\MembershipCardButtons;

final readonly class BodyTagMembershipCardTransformer implements BodyElementTransformer
{
    public function __construct(
        private BodyTransformerPipeline $pipeline,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function transform(BodyElement $element, BodyTransformContext $context): array
    {
        \assert($element instanceof BodyTagMembershipCard);

        return [
            'type' => $element->type(),
            'title' => $element->title(),
            'buttons' => $this->retrieveButtons($element->buttons(), $context->membershipLinks),
            'titleBanner' => $element->titleBanner(),
            'classBanner' => $element->classBanner(),
            'picture' => $this->pipeline->transformElement($element->bodyTagPictureMembership(), $context),
        ];
    }

    /**
     * @return class-string<BodyElement>
     */
    public function supports(): string
    {
        return BodyTagMembershipCard::class;
    }

    /**
     * @param array<string, mixed> $membershipLinks
     *
     * @return array<int, array<string, mixed>>
     */
    private function retrieveButtons(MembershipCardButtons $buttons, array $membershipLinks): array
    {
        $arrayButtons = [];
        /** @var array<string, string> $membershipLinkCombine */
        $membershipLinkCombine = $membershipLinks['membershipLinkCombine'] ?? [];

        /** @var MembershipCardButton $button */
        foreach ($buttons->buttons() as $button) {
            $url = $button->url();
            $urlMembership = $button->urlMembership();
            $arrayButtons[] = [
                'url' => $membershipLinkCombine[$url] ?? $url,
                'urlMembership' => $membershipLinkCombine[$urlMembership] ?? $urlMembership,
                'text' => $button->cta(),
            ];
        }

        return $arrayButtons;
    }
}
