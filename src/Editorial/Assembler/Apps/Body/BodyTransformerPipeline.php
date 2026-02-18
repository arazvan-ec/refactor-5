<?php

declare(strict_types=1);

namespace App\Editorial\Assembler\Apps\Body;

use App\Editorial\BodyTransformContext;
use Ec\Editorial\Domain\Model\Body\Body;
use Ec\Editorial\Domain\Model\Body\BodyElement;
use Ec\Editorial\Exceptions\BodyDataTransformerNotFoundException;
use Psr\Log\LoggerInterface;

final class BodyTransformerPipeline
{
    /** @var array<class-string<BodyElement>, BodyElementTransformer> */
    private array $transformers = [];

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function addTransformer(BodyElementTransformer $transformer): void
    {
        $this->transformers[$transformer->supports()] = $transformer;
    }

    /**
     * @return array<string, mixed>
     */
    public function transformBody(Body $body, BodyTransformContext $context): array
    {
        $parsedBody = [
            'type' => $body->type(),
            'elements' => [],
        ];

        /** @var BodyElement $bodyElement */
        foreach ($body->getArrayCopy() as $bodyElement) {
            try {
                $parsedBody['elements'][] = $this->transformElement($bodyElement, $context);
            } catch (BodyDataTransformerNotFoundException $exception) {
                $this->logger->info($exception->getMessage());

                continue;
            }
        }

        return $parsedBody;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws BodyDataTransformerNotFoundException
     */
    public function transformElement(BodyElement $element, BodyTransformContext $context): array
    {
        $class = $element::class;

        if (!isset($this->transformers[$class])) {
            throw new BodyDataTransformerNotFoundException(
                \sprintf('BodyElement data transformer type %s not found', $element->type())
            );
        }

        return $this->transformers[$class]->transform($element, $context);
    }
}
