<?php

namespace Mesolaries\SmartApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mesolaries_smart_api');

        $treeBuilder->getRootNode()
            ->children()
            ->arrayNode('smart_problem')->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('pattern')->defaultNull()->example('^/api')->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
