<?php
/**
 * Created by JetBrains PhpStorm.
 * User: blorenz
 * Date: 10.12.12
 * Time: 10:01
 * To change this template use File | Settings | File Templates.
 */

namespace Terrific\ExporterBundle\Helper {
    use Assetic\Asset\AssetInterface;
    use Assetic\Filter\FilterInterface;

    /**
     *
     */
    abstract class AsseticHelper {

        /**
         * Removes minification filter for validation.
         *
         * @param \Assetic\Asset\AssetInterface $origAsset
         * @return AssetInterface
         */
        public static function removeMinFilters(AssetInterface $origAsset) {
            $asset = clone $origAsset;

            $filters = $asset->getFilters();
            $asset->clearFilters();

            $removeFilter = array('Assetic\Filter\CssMinFilter', 'Assetic\Filter\JsMinFilter');

            /** @var $filter FilterInterface */
            foreach ($filters as $filter) {
                $removed = false;
                foreach ($removeFilter as $rFilter) {
                    if ($filter instanceof $rFilter) {
                        $removed = true;
                        break;
                    }
                }
                if (!$removed) {
                    $asset->ensureFilter($filter);
                }
            }

            return $asset;
        }

        /**
         *
         *
         * @param String $content
         * @return array
         */
        public static function retrieveImages($content) {
            $css = new \CssMin();
            $tokenList = $css->parse($content);

            $images = array();

            foreach ($tokenList as $token) {
                if (isset($token->Property)) {
                    switch ($token->Property) {
                        case "background":
                        case "background-image":
                            $matches = array();

                            if (preg_match('/url\(([^\)]+)/', $token->Value, $matches)) {
                                $tempfilename = trim($matches[1], '"\'');
                                // Do not export base64 image data uri
                                if(strtolower(substr($tempfilename, 0, 5)) !== "data:"){
                                    $images[] = $tempfilename;
                                }
                            }

                            break;
                    }
                }
            }

            return array_unique($images);
        }

        /**
         * @param $file
         * @param $css
         */
        public static function convertRelativeCSSPaths($file, $css) {
            $fs = new \Symfony\Component\Filesystem\Filesystem();

            if (!$fs->isAbsolutePath($file)) {
                $cssSplit = explode("/", dirname($css));
                $fileSplit = explode("/", $file);

                while ($fileSplit[0] == "..") {
                    array_shift($fileSplit);
                    unset($cssSplit[count($cssSplit) - 1]);
                }

                return implode("/", array_merge($cssSplit, $fileSplit));
            }

            return $file;
        }


        /**
         * @param $content
         * @return array
         */
        public static function retrieveFonts($content) {
            // Get help from CSSMin parser functionalities
            $css = new \CssMin();
            $tokenList = $css->parse($content);

            $fonts = array();

            foreach ($tokenList as $token) {
                if ($token instanceof \CssAtFontFaceDeclarationToken && ($token->Property == "src")) {
                    $matches = array();
                    if (preg_match_all('/url\(([^\)]+)/', $token->Value, $matches)) {
                        foreach ($matches[1] as $m) {
                            $fonts[] = trim($m, '"\'');

                        }
                    }
                }
            }

            $fonts = array_unique($fonts);

            array_walk($fonts, function (&$item) {
                // Catch '#' e.g. 'fontname.eot?#iefix'
                if (stristr($item, "#") !== false) {
                    list($font, $trash) = explode("#", $item);
                    $item = $font;
                }
                // Catch '?' e.g. 'fontname.eot?#iefix'
                if (stristr($item, "?") !== false) {
                    list($font, $trash) = explode("?", $item);
                    $item = $font;
                }
            });

            return $fonts;
        }

    }
}
