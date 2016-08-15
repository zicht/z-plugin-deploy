<?php
/**
 * For licensing information, please see the LICENSE file accompanied with this file.
 *
 * @author Gerard van Helden <drm@melp.nl>
 * @copyright 2012 Gerard van Helden <http://melp.nl>
 */
namespace Zicht\Tool\Plugin\Deploy;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Zicht\Tool\Container;
use Zicht\Tool\Plugin as BasePlugin;

/**
 * rsync plugin
 */
class Plugin extends BasePlugin
{
    /**
     * Configures the rsync parameters
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode
     * @return mixed|void
     */
    public function appendConfiguration(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('deploy')->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('strategy')
                            ->defaultValue('classic')
                        ->end()
                        ->scalarNode('deploymentPath')->end()
                        ->scalarNode('publicPath')->end()
                        ->scalarNode('keep')->defaultValue(2)->end()
                        ->arrayNode('shared')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Helper methods for deploy
     *
     * @param Container\Container $container
     *
     * @return void
     */
    public function setContainer(Container\Container $container)
    {
        parent::setContainer($container);

        $container->decl('getInstallPath', function () use ($container) {
            if ($container->get('deploy')['strategy'] === 'symlink') {
                return sprintf('%s/releases/%s', $container->get('deploy')['deploymentPath'], $container->get('targetPath'));
            } else {
                return $container->get('envs')[$container->get('target_env')]['root'];
            }
        });
    }
}