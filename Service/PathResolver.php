<?php
/**
 * Created by JetBrains PhpStorm.
 * User: blorenz
 * Date: 11.11.12
 * Time: 01:13
 * To change this template use File | Settings | File Templates.
 */
namespace Terrific\ExporterBundle\Service {
    use Symfony\Component\DependencyInjection\ContainerInterface;
    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    use Terrific\ComposerBundle\Service\ModuleManager;
    use Terrific\ExporterBundle\Exception\PathResolveException;
    use Symfony\Component\HttpKernel\Log\LoggerInterface;
    use Terrific\ExporterBundle\Component\FileLocator;
    use Symfony\Component\Filesystem\Filesystem;
    use Symfony\Component\Finder\Finder;
    use Symfony\Component\Finder\SplFileInfo;
    use Terrific\ExporterBundle\Helper\FileHelper;

    /**
     *
     */
    class PathResolver implements ContainerAwareInterface {
        // Bitfield

        // Scopes
        const SCOPE_MODULE = 1;
        const SCOPE_GLOBAL = 2;
        // Put an other scope here if needed ...

        // File types
        const TYPE_IMAGE = 8;
        const TYPE_FONT = 16;
        const TYPE_VIEW = 32;
        const TYPE_CSS = 64;
        const TYPE_JS = 128;
        const TYPE_DIFF = 256;
        const TYPE_CHANGELOG = 512;
        const TYPE_FLASH = 1024; // .swf (Shockwave Flash)
        const TYPE_SILVERLIGHT = 2048; // .xap (Silverlight zip archive)
        const TYPE_ICON = 4096; // .ico image/vnd.microsoft.icon + image/x-icon
        const TYPE_VIDEO = 8192; // .mp4, .flv, .ogv, .webm, .mpg, .mpeg, .mov
        const TYPE_AUDIO = 16384; // .mp3, .ogg, .wav, .acc
        const TYPE_JSON = 32768;

        /** @var ContainerInterface */
        protected $container;

        /** @var ModuleManager */
        protected $moduleManager;

        /** @var Array */
        protected $pathTemplate;

        /** @var Array */
        protected $modules = array();

        /** var LoggerInterface */
        protected $logger;

        /** @var FileLocator */
        protected $fileLocator;

        protected $initialized = false;

        /**
         * Setter for logger.
         *
         * @param \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
         * @return void
         */
        public function setLogger($logger) {
            $this->logger = $logger;
        }

        /**
         * Setter for modulemanager.
         *
         * @param \Terrific\ComposerBundle\Service\ModuleManager $moduleManager
         * @return void
         */
        public function setModuleManager(ModuleManager $moduleManager) {
            $this->moduleManager = $moduleManager;

            if ($this->moduleManager != null) {
                $moduleList = array();
                foreach ($this->moduleManager->getModules() as $module) {
                    $moduleList[] = $module->getName();
                }
                $this->setModules(array_unique($moduleList));
            }
        }

        /**
         * Setter for modulelist.
         *
         * @param Array $modules
         * @return void
         */
        public function setModules($modules) {
            $this->modules = $modules;
            if ($this->logger !== null) {
                $this->logger->debug("Set module list:\n\t" . implode(",\n\t", $this->modules) . "\n");
            }
        }

        /**
         * Getter for modulelist.
         *
         * @return Array
         */
        public function getModules() {
            return $this->modules;
        }

        /**
         * Setter for pathtemplates.
         *
         * @param Array $pathTemplate
         * @return void
         */
        public function setPathTemplate($pathTemplate) {
            $this->pathTemplate = $pathTemplate;
            if ($this->logger !== null) {
                $this->logger->debug("Set path templates : " . implode(",", $this->modules));
            }
        }

        /**
         * Getter for pathtemplates.
         *
         * @return Array
         */
        public function getPathTemplate() {
            return $this->pathTemplate;
        }

        /**
         * Check the modulename against the given ModuleList.
         *
         * @param $moduleName String
         * @return boolean
         */
        public function isValidModule($moduleName, $matchOnLower = false) {
            $ret = false;

            if ($moduleName != "") {
                if (in_array($moduleName, $this->modules)) {
                    $ret = true;
                }

                if (!$ret && $matchOnLower) {
                    foreach ($this->modules as $mod) {
                        if (strtolower($mod) === $moduleName) {
                            $ret = true;
                            break;
                        }
                    }
                }
            }

            if ($this->logger != null) {
                $this->logger->debug("Validating Module ['${moduleName}'] => " . ($ret ? "true" : "false"));
            }

            return $ret;
        }

        /**
         * Sets the Container.
         *
         * @param ContainerInterface $container A ContainerInterface instance
         * @return void
         *
         * @api
         */
        public function setContainer(ContainerInterface $container = null) {
            $this->container = $container;

            if ($container != null && $container->hasParameter("terrific_exporter")) {
                $config = $this->container->getParameter("terrific_exporter");
                if (isset($config["pathtemplates"])) {
                    $config = $config["pathtemplates"];

                    // Extend pathname config options

                    $a = array();
                    $a[(self::TYPE_IMAGE | self::SCOPE_GLOBAL)] = 'image';
                    $a[(self::TYPE_FONT | self::SCOPE_GLOBAL)] = 'font';
                    $a[(self::TYPE_CSS | self::SCOPE_GLOBAL)] = 'css';
                    $a[(self::TYPE_JS | self::SCOPE_GLOBAL)] = 'js';
                    $a[(self::TYPE_JSON | self::SCOPE_GLOBAL)] = 'json';
                    $a[(self::TYPE_VIEW | self::SCOPE_GLOBAL)] = 'view';
                    $a[(self::TYPE_FLASH | self::SCOPE_GLOBAL)] = 'flash';
                    $a[(self::TYPE_SILVERLIGHT | self::SCOPE_GLOBAL)] = 'silverlight';
                    $a[(self::TYPE_ICON | self::SCOPE_GLOBAL)] = 'icon';
                    $a[(self::TYPE_VIDEO | self::SCOPE_GLOBAL)] = 'video';
                    $a[(self::TYPE_AUDIO | self::SCOPE_GLOBAL)] = 'audio';

                    $a[(self::TYPE_CHANGELOG | self::SCOPE_GLOBAL)] = 'changelogs';
                    $a[(self::TYPE_DIFF | self::SCOPE_GLOBAL)] = 'diff';

                    $a[(self::TYPE_IMAGE | self::SCOPE_MODULE)] = 'module_image';
                    $a[(self::TYPE_FONT | self::SCOPE_MODULE)] = 'module_font';
                    $a[(self::TYPE_CSS | self::SCOPE_MODULE)] = 'module_css';
                    $a[(self::TYPE_JS | self::SCOPE_MODULE)] = 'module_js';
                    $a[(self::TYPE_JSON | self::SCOPE_MODULE)] = 'module_json';
                    $a[(self::TYPE_VIEW | self::SCOPE_MODULE)] = 'module_view';
                    $a[(self::TYPE_FLASH | self::SCOPE_MODULE)] = 'module_flash';
                    $a[(self::TYPE_SILVERLIGHT | self::SCOPE_MODULE)] = 'module_silverlight';
                    $a[(self::TYPE_VIDEO | self::SCOPE_MODULE)] = 'module_video';
                    $a[(self::TYPE_AUDIO | self::SCOPE_MODULE)] = 'module_audio';

                    foreach ($a as $key => $val) {
                        if (!empty($config[$val])) {
                            $this->pathTemplate[$key] = $config[$val];
                        }
                    }
                }
            }
        }

        /**
         * Cleans the given url.
         *
         * @param $sourcePath String
         * @return String
         */
        public function cleanPath($sourcePath) {
            $ret = $sourcePath;

            $pos = strpos($sourcePath, "?");
            if ($pos !== false) {
                $ret = substr($sourcePath, 0, $pos);
            }

            return $ret;
        }

        /**
         * Retrieves the modulename from the given path.
         *
         * @param $sourcePath String
         * @return String
         */
        public function getModuleName($sourcePath) {
            $dir = dirname($sourcePath);
            $ret = null;

            $matches = array();
            $modName = "";

            if (preg_match('#/terrificmodule([^/]+)#', $dir, $matches)) {
                $modName = trim($matches[1]);

                foreach ($this->modules as $mod) {
                    if (strtolower($mod) === $modName) {
                        $ret = $mod;
                        break;
                    }
                }

                if ($ret == null) {
                    throw new PathResolveException("Found a modulename but cannot find the correct spelling for module ['${modName}']");
                }
            }

            if ($ret === null) {
                $matches = array();
                if (preg_match('#src/Terrific/Module/([^/]+)#', $dir, $matches)) {
                    $modName = trim($matches[1]);
                    $ret = $modName;
                }
            }

            if ($this->logger !== null) {
                $this->logger->debug("Retrieving modulename from ['${sourcePath}'] => " . $modName);
            }

            return $ret;
        }

        /**
         * Retrieves the Scrope from the path.
         *
         * @param $sourcePath String
         * @return int
         */
        public function getScope($sourcePath) {
            $modName = $this->getModuleName($sourcePath);

            if ($modName != null) {
                $validMod = $this->isValidModule($modName, true);

                if ($modName != "" && !$validMod) {
                    throw new \Exception("Found an invalid Modulename");
                } else if ($modName != "" && $validMod) {
                    return self::SCOPE_MODULE;
                }
            }


            return self::SCOPE_GLOBAL;
        }

        /**
         * Returns the type of the Resource.
         *
         * @param $sourcePath String
         * @return int
         */
        public function getType($sourcePath) {
            $file = basename($sourcePath);

            $ext = strtoupper(pathinfo($file, PATHINFO_EXTENSION));

            // Extend this with types defined in const above
            switch ($ext) {
                case "GIF":
                case "JPG":
                case "JPEG":
                case "PNG":
                case "SVG":
                    return self::TYPE_IMAGE;

                case "EOT":
                case "TTF":
                case "WOFF":
                    return self::TYPE_FONT;

                case "CSS":
                    return self::TYPE_CSS;

                case "JS":
                    return self::TYPE_JS;

                case "JSON":
                    return self::TYPE_JSON;

                case "HTML":
                    return self::TYPE_VIEW;

                case "SWF":
                    return self::TYPE_FLASH;

                case "XAP":
                    return self::TYPE_SILVERLIGHT;

                case "ICO":
                    return self::TYPE_ICON;

                case "MP4":
                case "M4V":
                case "FLV":
                case "F4V":
                case "OGV":
                case "WEBM":
                case "MPG":
                case "MPEG":
                case "MOV":
                case "3GP":
                case "RM": // Real Media video
                    return self::TYPE_VIDEO;

                case "MP3":
                case "OGG":
                case "WAV":
                case "AAC":
                case "M4A":
                case "WMA":
                case "F4A":
                case "F4B": // Audiobook for flash player
                    return self::TYPE_AUDIO;

                case "DIFF":
                    return self::TYPE_DIFF;
                    break;

                case "MD":
                case "TXT":
                case "LOG":
                    return self::TYPE_CHANGELOG;
                    break;


            }
        }

        /**
         * @param $value
         */
        private function getConstantName($value) {
            $ref = new \ReflectionClass($this);

            foreach ($ref->getConstants() as $name => $val) {
                if ($val == $value) {
                    return $name;
                }
            }

            return "N/A";
        }


        /**
         * @param $filePath
         */
        public function buildWebPath($filePath) {
            if (file_exists($filePath)) {

                $file = realpath($filePath);

                if (!$file) {
                    throw new \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException("Cannot build a webpath from a non existing path: " . $file);
                }

                $modName = $this->getModuleName($file);
                $file = substr($file, strpos($file, "public/") + 7);
                $file = "/bundles/terrificmodule" . strtolower($modName) . "/${file}";

                return $file;
            }

            return $filePath;
        }


        /**
         * @param $file File name and extension
         */
        public function locate($file, $assertedPath = "") {
            $this->initialize();

            if (file_exists($assertedPath)) {
                $assertedPath = realpath($assertedPath);
                $assertedPath = str_replace(realpath($this->container->getParameter("kernel.root_dir") . "/../") . "/", "", $assertedPath);
            }

            $assertedPath = ltrim($assertedPath, ".");

            $bundleLookup = null;

            if (strpos($assertedPath, "/terrificmodule") !== false) {
                $modName = $this->getModuleName($assertedPath);

                $modPath = "/terrificmodule" . strtolower($modName) . "/";
                $pos = strpos($assertedPath, $modPath) + strlen($modPath);
                $path = substr($assertedPath, $pos);

                $nPath = "src/Terrific/Module/" . $modName . "/Resources/public/" . $path;

                $assertedPath = $nPath;
            }

            if (strpos($assertedPath, DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR) === 0) {
                list($_, $_, $bundleName) = explode(DIRECTORY_SEPARATOR, $assertedPath);

                // found bundle
                $nPath = str_replace(DIRECTORY_SEPARATOR . "bundles" . DIRECTORY_SEPARATOR . $bundleName . DIRECTORY_SEPARATOR, "", $assertedPath);
                $bundleLookup = $nPath;
            }

            // var_dump($file);
            $locatedFiles = $this->fileLocator->locate($file, null, false);

            $ret = array();
            foreach ($locatedFiles as $file) {
                $file = realpath($file);

                if ($bundleLookup !== null) {
                    $found = (strpos($file, $bundleLookup) !== false);
                } else {
                    $found = (strpos($file, $assertedPath) !== false);
                }


                if ($this->logger !== null) {
                    $this->logger->debug(sprintf("Search for\n\t['%s'] in\n\t['%s'] => %s", $assertedPath, $file, ($found ? 'true' : 'false')));
                }

                if ($found) {
                    $ret[] = $file;
                }
            }

            //if (count($ret) == 1) { //remove termporary due to problems (data is found in module and web folder)
                return $ret[0];
            //}

            if ($this->logger !== null) {
                $this->logger->err(print_r($locatedFiles, true));
            }
            throw new \Exception(sprintf("Cannot identify single path for asset: %s [checked against %s]", $file, $assertedPath));
        }

        /**
         * Builds a new path for the given path. The Path is generated against configuration settings.
         *
         * @param $sourcePath string
         * @param $forcedScope int
         * @param $forcedType int
         * @return string
         */
        public function resolve($sourcePath, $forcedScope = null, $forcedType = null) {
            $ret = "";
            $sourcePath = $this->cleanPath($sourcePath);
            $scope = ($forcedScope != null ? $forcedScope : $this->getScope($sourcePath));
            $type = ($forcedType != null ? $forcedType : $this->getType($sourcePath));

            $tpl = $this->pathTemplate[($type | $scope)];

            if ($scope === self::SCOPE_MODULE) {
                $modName = $this->getModuleName($sourcePath);

                $ret = str_replace("%module%", $modName, $tpl);
            } elseif ($scope === self::SCOPE_GLOBAL) {
                $ret = $tpl;
            }

            if (FileHelper::isImage($sourcePath)) {
                $ret .= "/" . substr($sourcePath, strpos($sourcePath, "img/") + 4);
            } else {
                if (strpos($sourcePath, "dependencies") !== false) {
                    $ret .= "/dependencies/" . substr($sourcePath, strpos($sourcePath, "dependencies/") + 13);
                } else {
                    $ret .= "/" . basename($sourcePath);
                }
            }

            $ret = FileHelper::cleanPath($ret);

            if ($this->logger !== null) {
                $type = $this->getConstantName($type);
                $scope = $this->getConstantName($scope);
                $this->logger->debug("Resolving path\n\t['${sourcePath}'] => ['${ret}'],\n\ttype = ${type},\n\tscope = ${scope}");
            }

            return $ret;
        }


        /**
         * @param $path
         */
        public function addResourcePath($path) {
            $this->fileLocator->addPath($path);
        }


        /**
         *
         *
         * @param $module
         * @param $parentModulePath parent path of modules
         * @return array
         */
        public function buildModuleResourcePaths($module, $parentModulePath) {
            $locations = array();

            $locations[] = $parentModulePath . "/${module}/Resources/public/img";
            $locations[] = $parentModulePath . "/${module}/Resources/public/flash";
            $locations[] = $parentModulePath . "/${module}/Resources/public/font";
            $locations[] = $parentModulePath . "/${module}/Resources/public/fonts";
            $locations[] = $parentModulePath . "/${module}/Resources/public/video";
            $locations[] = $parentModulePath . "/${module}/Resources/public/json";

            return $locations;
        }

        /**
         *
         */
        protected function initialize($reInitialize = false) {
            if (!$this->initialized || $reInitialize) {
                $this->initialized = true;

                if ($this->container && count($this->modules)) {
                    $finder = new Finder();

                    /** @var $fs Filesystem */
                    $fs = $this->container->get("filesystem");

                    $root_dir = $this->container->getParameter("kernel.root_dir");

                    // Hard coded location paths for WHERE should be searched
                    $locations = array(
                        $root_dir . "/../web", $root_dir . "/../web/img", $root_dir . "/../web/font", $root_dir . "/../web/fonts", $root_dir . "/../web/video", $root_dir . "/../web/json"
                    );

                    $root_dir .= "/../src/Terrific/Module";
                    foreach ($this->modules as $module) {
                        $locations = array_merge($locations, $this->buildModuleResourcePaths($module, $root_dir));
                    }

                    $locations = array_filter($locations, function ($itm) use ($fs) {
                        return $fs->exists($itm);
                    });

                    $finder->in($locations);

                    /** @var $dir SplFileInfo */
                    foreach ($finder->directories() as $dir) {
                        $locations[] = $dir->getPathname();
                    }

                    if ($this->logger !== null) {
                        $this->logger->debug(sprintf("Use pathlist for location \n[\n\t%s\n].\n", implode(",\n\t", $locations)));
                    }

                    $this->fileLocator->addPath($locations);
                }
            }
        }

        /**
         * Constructor
         */
        public function __construct() {
            $this->pathTemplate = array();

            // Extend this ... if you need some more. Don't forget to update the getType function.
            // Global context
            $this->pathTemplate[(self::TYPE_IMAGE | self::SCOPE_GLOBAL)] = '/img/common';
            $this->pathTemplate[(self::TYPE_FONT | self::SCOPE_GLOBAL)] = '/fonts';
            $this->pathTemplate[(self::TYPE_CSS | self::SCOPE_GLOBAL)] = '/css';
            $this->pathTemplate[(self::TYPE_JS | self::SCOPE_GLOBAL)] = '/js';
            $this->pathTemplate[(self::TYPE_JSON | self::SCOPE_GLOBAL)] = '/json';
            $this->pathTemplate[(self::TYPE_VIEW | self::SCOPE_GLOBAL)] = '/views';
            $this->pathTemplate[(self::TYPE_CHANGELOG | self::SCOPE_GLOBAL)] = '/changelogs';
            $this->pathTemplate[(self::TYPE_DIFF | self::SCOPE_GLOBAL)] = '/changelogs/diff';
            $this->pathTemplate[(self::TYPE_FLASH | self::SCOPE_GLOBAL)] = '/flash';
            $this->pathTemplate[(self::TYPE_SILVERLIGHT | self::SCOPE_GLOBAL)] = '/silverlight';
            $this->pathTemplate[(self::TYPE_ICON | self::SCOPE_GLOBAL)] = '/';
            $this->pathTemplate[(self::TYPE_VIDEO | self::SCOPE_GLOBAL)] = '/media/video';
            $this->pathTemplate[(self::TYPE_AUDIO | self::SCOPE_GLOBAL)] = '/media/audio';

            // Module context
            $this->pathTemplate[(self::TYPE_IMAGE | self::SCOPE_MODULE)] = '/img/%module%';
            $this->pathTemplate[(self::TYPE_FONT | self::SCOPE_MODULE)] = '/fonts/%module%';
            $this->pathTemplate[(self::TYPE_CSS | self::SCOPE_MODULE)] = '/css/%module%';
            $this->pathTemplate[(self::TYPE_JS | self::SCOPE_MODULE)] = '/js/%module%';
            $this->pathTemplate[(self::TYPE_JSON | self::SCOPE_MODULE)] = '/json/%module%';
            $this->pathTemplate[(self::TYPE_VIEW | self::SCOPE_MODULE)] = '/views/%module%';
            $this->pathTemplate[(self::TYPE_FLASH | self::SCOPE_MODULE)] = '/flash/%module%';
            $this->pathTemplate[(self::TYPE_SILVERLIGHT | self::SCOPE_MODULE)] = '/silverlight/%module%';
            $this->pathTemplate[(self::TYPE_VIDEO | self::SCOPE_MODULE)] = '/media/video/%module%';
            $this->pathTemplate[(self::TYPE_AUDIO | self::SCOPE_MODULE)] = '/media/audio/%module%';

            $this->modules = array();
            $this->moduleManager = null;
            $this->container = null;

            $this->fileLocator = new FileLocator();
        }
    }
}
