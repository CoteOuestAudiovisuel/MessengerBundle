<?php
namespace Coa\MessengerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * CoaMessengerExtension
 */
class CoaMessengerExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('coa_messenger', $config);

        foreach ($config as $k=>$v){
            $container->setParameter("coa_messenger.$k", $v);
        }
    }

    public function prepend(ContainerBuilder $container){
        // creation du fichier de configuration
        $config_path = $container->getParameter('kernel.project_dir')."/config/packages/coa_messenger.yaml";
        if(!file_exists($config_path)){
            copy(__DIR__.'/../Resources/config/packages/coa_messenger.yaml',$config_path);
        }
    }
}