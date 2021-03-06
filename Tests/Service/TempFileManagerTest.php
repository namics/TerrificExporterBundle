<?php
namespace Terrific\ExporterBundle\Tests\Service {
    use Terrific\ExporterBundle\Service\TempFileManager;

    /**
     * Generated by PHPUnit_SkeletonGenerator on 2012-11-11 at 14:56:35.
     */
    class TempFileManagerTest extends \PHPUnit_Framework_TestCase {
        /**
         * @var TempFileManager
         */
        protected $object;

        /**
         * Sets up the fixture, for example, opens a network connection.
         * This method is called before a test is executed.
         */
        protected function setUp() {
            $this->object = new TempFileManager();
            $this->object->setTempDir(__DIR__ . "/../App/app/cache");
        }

        /**
         * Tears down the fixture, for example, closes a network connection.
         * This method is called after a test is executed.
         */
        protected function tearDown() {
            $this->object->cleanup();
        }


        /**
         * @covers Terrific\ExporterBundle\Service\TempFileManager::generateFilename
         */
        public function testGenerateFilename() {
            $this->assertNotSame("", $this->object->generateFileName());
        }

        /**
         * @covers Terrific\ExporterBundle\Service\TempFileManager::putContent
         */
        public function testPutContent() {
            $file = $this->object->putContent("DATA");

            $this->assertTrue(is_file($file), "No temp file were created");
            $this->assertEquals(file_get_contents($file), "DATA", "Data were not correctly written to the created file");
        }


    }
}
