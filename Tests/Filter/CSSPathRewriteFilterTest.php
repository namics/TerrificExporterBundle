<?php
namespace Terrific\ExporterBundle\Tests\Filter {
    use Terrific\ExporterBundle\Helper\FileHelper;
    use Terrific\ExporterBundle\Filter\CSSPathRewriteFilter;
    use Terrific\ExporterBundle\Service\PathResolver;
    use Assetic\Asset\AssetCollection;
    use Assetic\Asset\StringAsset;

    /**
     * Generated by PHPUnit_SkeletonGenerator on 2012-09-08 at 09:30:13.
     */
    class CSSPathRewriteFilterTest extends \PHPUnit_Framework_TestCase {
        /**
         * @var CSSPathRewriteFilter
         */
        protected $object;

        /** @var \Symfony\Component\DependencyInjection\Container */
        protected $container;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp() {
            $this->object = new CSSPathRewriteFilter();
            $this->object->setPathResolver(new PathResolver());

            $this->container = new \Symfony\Component\DependencyInjection\Container();
            $this->container->setParameter("terrific_exporter", array("build_local_paths" => true));

            $this->object->setContainer($this->container);
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown() {
        }


        /**
         * @covers CSSPathRewriteFilterTest::filterDump()
         */
        public function testFilterDump() {
            $cssContent = "body { background-image: url('/img/macht/die/kuh.jpg'); }";

            $css = new StringAsset($cssContent, array(), "", "sourcePath/");
            $css->setTargetPath("targetPath/test.css");

            $css->dump();
            $this->object->filterDump($css);
            $ret = $css->dump();

            $this->assertEquals("body { background-image: url('../img/common/macht/die/kuh.jpg'); }", $ret);
        }


        /**
         * @covers CSSPathRewriteFilterTest::filterDump()
         */
        public function testFilterDumpConfigSettings() {
            $this->container->setParameter("terrific_exporter", array("build_local_paths" => false));
            $cssContent = "body { background-image: url('/img/macht/die/kuh.jpg'); }";

            $css = new StringAsset($cssContent, array(), "", "sourcePath/");
            $css->setTargetPath("targetPath/test.css");

            $css->dump();
            $this->object->filterDump($css);
            $ret = $css->dump();

            $this->assertEquals($cssContent, $ret);
        }
    }
}
