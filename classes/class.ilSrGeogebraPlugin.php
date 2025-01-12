<?php

require_once __DIR__ . "/../vendor/autoload.php";

use ILIAS\DI\Container;
use srag\CustomInputGUIs\SrGeogebra\Loader\CustomInputGUIsLoaderDetector;
use srag\Plugins\SrGeogebra\Upload\UploadService;
use srag\Plugins\SrGeogebra\Utils\SrGeogebraTrait;
//use srag\RemovePluginDataConfirm\SrGeogebra\PluginUninstallTrait;

/**
 * Class ilSrGeogebraPlugin
 *
 * Generated by SrPluginGenerator v1.3.4
 *
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 */
class ilSrGeogebraPlugin extends ilPageComponentPlugin
{

   // use PluginUninstallTrait;
    use SrGeogebraTrait;
    const PLUGIN_ID = "srgg";
    const PLUGIN_NAME = "SrGeogebra";
    const PLUGIN_CLASS_NAME = self::class;
    const DIRECTORY = "Customizing/global/plugins/Services/COPage/PageComponent/SrGeogebra";
    // Can't be in language file as languages aren't updated on a failed update
    const UPDATE_ERROR_MSG = "Update failed. Please add the file extension \"ggb\" into the ILIAS file upload whitelist. Please consult the documentation for more info.";
    /**
     * @var self|null
     */
    protected static $instance = null;


    /**
     * @return self
     */
    public static function getInstance() : self
    {
        GLOBAL $DIC;
        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC["component.factory"];
        return $component_factory->getPlugin('srgg');
    }

    public function getPrefix() : string
    {
        return self::PLUGIN_ID;
    }

    /**
     * ilSrGeogebraPlugin constructor
     */
    public function __construct(
        \ilDBInterface $db,
        \ilComponentRepositoryWrite $component_repository,
        string $id
    )
    {
        parent::__construct($db, $component_repository, $id);
    }


    /**
     * @inheritDoc
     */
    public function getPluginName() : string
    {
        return self::PLUGIN_NAME;
    }


    /**
     * @inheritDoc
     */
    public function isValidParentType(/*string*/ $a_type) : bool
    {
        // Allow in all parent types
        return true;
    }


    /**
     * @inheritDoc
     */
    public function onDelete(
        array $a_properties,
        string $a_plugin_version,
        bool $move_operation = false
    ): void
    {
        global $DIC;
        if ($DIC->ctrl()->getCmd() !== "moveAfter") {

        }
    }


    /**
     * @inheritDoc
     */
    public function onClone(
        array &$a_properties,
        string $a_plugin_version
    ): void
    {

    }


    /**
     * @inheritDoc
     */
    public function updateLanguages(/*?array*/ $a_lang_keys = null)/*:void*/
    {
        parent::updateLanguages($a_lang_keys);

        $this->installRemovePluginDataConfirmLanguages();
    }


    /**
     * @inheritDoc
     */
    protected function deleteData()/*: void*/
    {
        self::srGeogebra()->dropTables();
    }


    protected function beforeActivation(): bool
    {
      //  return $this->checkSuffixAvailable(self::plugin()->translate("config_activation_error"));
        return true;
    }


    protected function beforeUpdate(): bool
    {
       // return $this->checkSuffixAvailable(self::UPDATE_ERROR_MSG);
        return true;
    }



    protected function checkSuffixAvailable($error_msg) {
        /*global $DIC;

        $whitelist = ilFileUtils::getValidExtensions();

        // Error if file extension "ggb" is not whitelisted upon plugin activation
        if (!in_array(UploadService::FILE_SUFFIX, $whitelist)) {
            ilUtil::sendFailure($error_msg, true);
            return true;
        }
*/
        return true;
    }


    /**
     * @return bool
     */
    protected function shouldUseOneUpdateStepOnly() : bool
    {
        return false;
    }


    public function exchangeUIRendererAfterInitialization(Container $dic) : Closure
    {
        return CustomInputGUIsLoaderDetector::exchangeUIRendererAfterInitialization();
    }
}
