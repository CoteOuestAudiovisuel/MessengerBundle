<?php
namespace Coa\MessengerBundle;
use Coa\MessengerBundle\DependencyInjection\CoaMessengerExtension;


use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CoaMessengerBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $ext = new CoaMessengerExtension([],$container);
    }

    /**
     * {@inheritdoc}
     */
    public function registerCommands(Application $application)
    {
        // noop
    }
}