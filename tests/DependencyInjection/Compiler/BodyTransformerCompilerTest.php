<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\BodyTransformerCompiler;
use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(BodyTransformerCompiler::class)]
final class BodyTransformerCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function process(): void
    {
        $pipelineDefinition = new Definition();
        $this->setDefinition(BodyTransformerPipeline::class, $pipelineDefinition);

        $transformerDefinition = new Definition();
        $transformerDefinition->addTag('app.body_element_transformer');
        $this->setDefinition('body_element_transformer_service', $transformerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            BodyTransformerPipeline::class,
            'addTransformer',
            [
                $transformerDefinition,
            ],
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $pipelineDefinition = new Definition(BodyTransformerPipeline::class);
        $container->setDefinition(BodyTransformerPipeline::class, $pipelineDefinition);

        $container->addCompilerPass(new BodyTransformerCompiler());
    }
}
