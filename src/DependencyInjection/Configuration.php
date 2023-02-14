<?php
namespace Coa\MessengerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class Configuration implements ConfigurationInterface
{
    function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('coa_messenger');

        $rootNode = $builder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('handlers')
                    ->scalarPrototype()->end()
                ->end()
            ->end();

        return $builder;
    }
}