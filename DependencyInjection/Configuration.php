<?php

namespace Padam87\ErrorLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('padam87_error_log');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('ignored_exceptions')
                    ->arrayPrototype()
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
                ->scalarNode('entity_manager_name')
                    ->defaultValue('default')
                    ->info('The entity manager used to store the logs.')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
