<?php

/**
 * @copyright
 */

declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Application\DataTransformer\Apps\Media\DataTransformers\Widget\DetailWidgetDataTransformerHandler;
use App\Editorial\Assembler\Apps\Media\Widget\WidgetTransformerPipeline;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @author Laura Gómez Cabero <lgomez@ext.elconfidencial.com>
 */
class WidgetDataTransformerCompiler implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definedServiceTags = $container->findTaggedServiceIds('ec.widget.dataTransformer');
        $dataTransformersHandler = $container->findDefinition(DetailWidgetDataTransformerHandler::class);

        foreach ($definedServiceTags as $idService => $parameters) {
            $definition = $container->getDefinition($idService);
            $dataTransformersHandler->addMethodCall('addDataTransformer', [$definition]);
        }

        $newTaggedServices = $container->findTaggedServiceIds('app.widget_type_transformer');

        if ($container->hasDefinition(WidgetTransformerPipeline::class)) {
            $pipelineDefinition = $container->findDefinition(WidgetTransformerPipeline::class);

            foreach ($newTaggedServices as $idService => $parameters) {
                $definition = $container->getDefinition($idService);
                $pipelineDefinition->addMethodCall('addTransformer', [$definition]);
            }
        }
    }
}
