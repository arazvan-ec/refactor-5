<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Editorial\Assembler\Apps\Media\MediaTransformerPipeline;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MediaTransformerCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('app.media_transformer');
        $pipelineDefinition = $container->findDefinition(MediaTransformerPipeline::class);

        foreach ($taggedServices as $idService => $parameters) {
            $definition = $container->getDefinition($idService);
            $pipelineDefinition->addMethodCall('addTransformer', [$definition]);
        }
    }
}
