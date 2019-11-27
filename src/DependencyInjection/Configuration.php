<?php

namespace Mesavolt\ImagingBundle\DependencyInjection;


use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('imaging');

        // symfony =< 4.1 compatibility
        // taken from https://github.com/sensiolabs/SensioFrameworkExtraBundle/pull/594/files
        $rootNode = method_exists($treeBuilder, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root('imaging');

        $rootNode
            ->children()
                ->scalarNode('transparency_replacement')->defaultValue('#FFFFFF')->end()
            ->end();

        return $treeBuilder;

    }
}
