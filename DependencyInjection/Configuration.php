<?php

namespace Padam87\ErrorLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('padam87_monolog_extra');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('ignored_exceptions')
                    ->arrayPrototype()
                        ->scalarPrototype()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
