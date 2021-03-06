<?php
/**
 * Created by JetBrains PhpStorm.
 * User: blorenz
 * Date: 12.11.12
 * Time: 14:16
 * To change this template use File | Settings | File Templates.
 */
namespace Terrific\ExporterBundle\Actions {
    use Symfony\Component\Console\Output\OutputInterface;
    use Terrific\ExporterBundle\Object\ActionResult;
    use Terrific\ExporterBundle\Service\TempFileManager;
    use Terrific\ExporterBundle\Service\PageManager;
    use Terrific\ExporterBundle\Object\Route;
    use Symfony\Bundle\FrameworkBundle\HttpKernel;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Terrific\ExporterBundle\Service\W3CValidator;
    use Terrific\ExporterBundle\Object\ActionRequirement;
    use Terrific\ExporterBundle\Object\RouteLocale;

    /**
     *
     */
    class ValidateViews extends AbstractValidateAction implements IAction {
        /**
         * Returns requirements for running this Action.
         *
         * @param \Symfony\Component\Console\Output\OutputInterface $output
         * @param array $params
         * @param array $runnedActions
         * @return array
         */
        public static function getRequirements() {
            $ret = array();

            $ret[] = new ActionRequirement("curl", ActionRequirement::TYPE_PHPEXT, 'Terrific\ExporterBundle\Actions\ValidateViews');

            return $ret;
        }

        /**
         * Return true if the action should be runned false if not.
         *
         * @param array $params
         * @return bool
         */
        public function isRunnable(array $params) {
            return (isset($params["validate_html"]) && $params["validate_html"] && isset($params["export_views"]) && $params["export_views"]);
        }

        /**
         * @param $params
         * @return ActionResult
         */
        public function run(OutputInterface $output, $params = array()) {
            /** @var $tmpFileMgr TempFileManager */
            $tmpFileMgr = $this->container->get("terrific.exporter.tempfilemanager");

            /** @var $pageManager PageManager */
            $pageManager = $this->container->get("terrific.exporter.pagemanager");

            /** @var $w3Validator W3CValidator */
            $w3Validator = $this->container->get("terrific.exporter.w3validator");

            $error = false;

            /** @var $route Route */
            foreach ($pageManager->findRoutes(true) as $route) {
                if (!$route->isLocalized()) {
                    $resp = $pageManager->dumpRoute($route);
                    $file = $tmpFileMgr->putContent($resp->getContent());

                    $results = $w3Validator->validateFile($file);
                    $this->processValidationResults($results, $route->getExportName());

                    if ($results->hasErrors()) {
                        $error = true;
                    }
                } else {
                    /** @var $locale RouteLocale */
                    foreach ($route->getLocales() as $locale) {
                        $resp = $pageManager->dumpRoute($route, $locale->getLocale());
                        $file = $tmpFileMgr->putContent($resp->getContent());

                        $results = $w3Validator->validateFile($file);
                        $this->processValidationResults($results, $locale->getExportName());

                        if ($results->hasErrors()) {
                            $error = true;
                        }
                    }
                }

            }

            if ($error) {
                //return new ActionResult(ActionResult::STOP);
            }

            return new ActionResult(ActionResult::OK);
        }
    }
}
