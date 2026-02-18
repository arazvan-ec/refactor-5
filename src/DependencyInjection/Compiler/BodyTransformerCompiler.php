<?php

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Editorial\Assembler\Apps\Body\BodyTransformerPipeline;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BodyTransformerCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $transformers = $container->findTaggedServiceIds('app.body_element_transformer');
        $pipeline = $container->findDefinition(BodyTransformerPipeline::class);

        foreach ($transformers as $idService => $parameters) {
            $definition = $container->getDefinition($idService);
            $pipeline->addMethodCall('addTransformer', [$definition]);
        }
    }
}
