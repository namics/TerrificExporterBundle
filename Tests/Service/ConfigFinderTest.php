<?php
namespace Terrific\ExporterBundle\Tests\Service {
    use Terrific\ExporterBundle\Service\ConfigFinder;

    /**
     * Generated by PHPUnit_SkeletonGenerator on 2012-11-11 at 14:56:35.
     */
    class ConfigFinderTest extends \PHPUnit_Framework_TestCase {
        /**
         * @var ConfigFinder
         */
        protected $object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp() {
            $this->object = new ConfigFinder();
            $this->object->setWorkingPath(__DIR__ . "/../App");
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown() {
        }


        /**
         * @covers ConfigFinder::find()
         * @expectedException InvalidArgumentException
         */
        public function testFind() {
            // get first appeaarance of file in project
            $this->assertEquals(realpath(__DIR__ . "/../App/app/config/jshint.json"), $this->object->find("jshint.json"));

            // get appearance in default settings
            $this->assertEquals(realpath(__DIR__ . "/../../Resources/config/build.ini"), $this->object->find("build.ini"));

            // Exception !
            $this->object->find("not-available-file.ext");
        }

    }
}