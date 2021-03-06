<?php
/**
 * Created by JetBrains PhpStorm.
 * User: blorenz
 * Date: 04.09.12
 * Time: 10:10
 * To change this template use File | Settings | File Templates.
 */

namespace Terrific\ExporterBundle\DependencyInjection {
    use Symfony\Component\HttpKernel\DependencyInjection\Extension;
    use Symfony\Component\DependencyInjection\ContainerBuilder;
    use Symfony\Component\HttpKernel\Bundle\Bundle;
    use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
    use Symfony\Component\Config\FileLocator;
    use Terrific\ExporterBundle\DependencyInjection\Configuration\Configuration;
    use Terrific\ExporterBundle\Factory\LazyAssetManager;
    use ErrorException;


    /**
     *
     */
    class TerrificExporterExtension extends Extension {

        /**
         * @param $errno
         * @param $errstr
         * @param $errfile
         * @param $errline
         * @param array $errcontext
         * @throws ErrorException
         */
        public function handleError($errno, $errstr, $errfile, $errline, array $errcontext) {
            throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
        }


        /**
         * @param array $configs
         * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
         */
        public function load(array $configs, ContainerBuilder $container) {
            set_error_handler(array($this, 'handleError'));

            $configuration = new Configuration();
            $config = $this->processConfiguration($configuration, $configs);

            $container->setParameter("terrific_exporter", $config);
        }
    }
}
