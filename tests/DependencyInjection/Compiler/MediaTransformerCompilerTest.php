<?php

declare(strict_types=1);

namespace App\Tests\DependencyInjection\Compiler;

use App\DependencyInjection\Compiler\MediaTransformerCompiler;
use App\Editorial\Assembler\Apps\Media\MediaTransformerPipeline;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(MediaTransformerCompiler::class)]
final class MediaTransformerCompilerTest extends AbstractCompilerPassTestCase
{
    #[Test]
    public function shouldRegisterTaggedServicesWithPipeline(): void
    {
        $pipelineDefinition = new Definition();
        $this->setDefinition(MediaTransformerPipeline::class, $pipelineDefinition);

        $transformerDefinition = new Definition();
        $transformerDefinition->addTag('app.media_transformer');
        $this->setDefinition('media_transformer_service', $transformerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            MediaTransformerPipeline::class,
            'addTransformer',
            [
                $transformerDefinition,
            ]
        );
    }

    #[Test]
    public function shouldRegisterMultipleTaggedServices(): void
    {
        $pipelineDefinition = new Definition();
        $this->setDefinition(MediaTransformerPipeline::class, $pipelineDefinition);

        $photoTransformerDefinition = new Definition();
        $photoTransformerDefinition->addTag('app.media_transformer');
        $this->setDefinition('photo_transformer_service', $photoTransformerDefinition);

        $videoTransformerDefinition = new Definition();
        $videoTransformerDefinition->addTag('app.media_transformer');
        $this->setDefinition('video_transformer_service', $videoTransformerDefinition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            MediaTransformerPipeline::class,
            'addTransformer',
            [
                $photoTransformerDefinition,
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            MediaTransformerPipeline::class,
            'addTransformer',
            [
                $videoTransformerDefinition,
            ]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $pipelineDefinition = new Definition(MediaTransformerPipeline::class);
        $container->setDefinition(MediaTransformerPipeline::class, $pipelineDefinition);

        $container->addCompilerPass(new MediaTransformerCompiler());
    }
}
