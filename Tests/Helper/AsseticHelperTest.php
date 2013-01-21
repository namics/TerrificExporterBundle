<?php
namespace Terrific\ExporterBundle\Tests\Filter {
    use Terrific\ExporterBundle\Helper\AsseticHelper;

    /**
     * Generated by PHPUnit_SkeletonGenerator on 2012-09-08 at 09:30:13.
     */
    class AsseticHelperTest extends \PHPUnit_Framework_TestCase {
        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp() {
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown() {
        }


        /**
         * @covers AsseticHelper::retrieveImages()
         */
        public function testRetrieveImages() {
            $content = "body { background:url('/test/img.png') no-repeat top left; }";
            $this->assertContains("/test/img.png", AsseticHelper::retrieveImages($content));

            $content = "body { background-image:url('/test22/imgasdf.jpg'); }";
            $this->assertContains("/test22/imgasdf.jpg", AsseticHelper::retrieveImages($content));
        }


        /**
         * @covers AsseticHelper::retrieveFonts()
         */
        public function testRetrieveFonts() {
            $content = '@font-face { font-family: "FrutigerLTW01-45Light"; src: url("/font/frutiger-45-light.eot?iefix"); src: url("/font/frutiger-45-light.woff") format("woff"), url("/font/frutiger-45-light.ttf") format("truetype"), url("/font/frutiger-45-light.svg#3f5a5b87-e71e-4544-be0c-da4daa132710") format("svg"); }';

            $assets = AsseticHelper::retrieveFonts($content);

            $this->assertContains("/font/frutiger-45-light.eot", $assets);
            $this->assertContains("/font/frutiger-45-light.woff", $assets);
            $this->assertContains("/font/frutiger-45-light.ttf", $assets);
            $this->assertContains("/font/frutiger-45-light.svg", $assets);
        }

        /**
         * @covers AsseticHelper::convertRelativeCSSPaths()
         */
        public function testConvertRelativeCSSPaths() {
            $cssFile = __DIR__ . "/../App/web/css/test.css";
            $target = "../img/blub.jpg";

            $ret = AsseticHelper::convertRelativeCSSPaths($target, $cssFile);

            $this->assertEquals(realpath(__DIR__ . "/../App/web/img/blub.jpg"), realpath($ret));
        }
    }
}
