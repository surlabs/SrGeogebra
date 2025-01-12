<?php

use ILIAS\FileUpload\DTO\ProcessingStatus;
use ILIAS\FileUpload\DTO\UploadResult;
use ILIAS\FileUpload\Location;
use srag\ActiveRecordConfig\SrGeogebra\Config\Config;
use srag\Plugins\SrGeogebra\Config\ConfigAdvancedGeogebraFormGUI;
use srag\Plugins\SrGeogebra\Config\GlobalConfigTransformer;
use srag\Plugins\SrGeogebra\Config\Repository;
use srag\Plugins\SrGeogebra\Forms\GeogebraFormGUI;
use srag\Plugins\SrGeogebra\Forms\SettingsAdvancedGeogebraFormGUI;
use srag\Plugins\SrGeogebra\Upload\UploadService;
use srag\Plugins\SrGeogebra\Utils\SrGeogebraTrait;
use srag\DIC\SrGeogebra\DICTrait;

/**
 * Class ilSrGeogebraPluginGUI
 *
 * Generated by SrPluginGenerator v1.3.4
 *
 * @author studer + raimann ag - Team Custom 1 <support-custom1@studer-raimann.ch>
 *
 * @ilCtrl_isCalledBy ilSrGeogebraPluginGUI: ilPCPluggedGUI
 */
class ilSrGeogebraPluginGUI extends ilPageComponentPluginGUI
{

    use DICTrait;
    use SrGeogebraTrait;
    const PLUGIN_CLASS_NAME = ilSrGeogebraPlugin::class;
    const CMD_CANCEL = "cancel";
    const CMD_CREATE = "create";
    const CMD_CREATE_ADVANCED = "createAdvanced";
    const CMD_EDIT = "edit";
    const CMD_EDIT_ADVANCED = "editAdvanced";
    const CMD_INSERT = "insert";
    const CMD_UPDATE = "update";
    const CMD_UPDATE_ADVANCED_PROPERTIES = "updateAdvancedProperties";
    const SUBTAB_GENERIC_SETTINGS = "subtab_generic_settings";
    const SUBTAB_ADVANCED_SETTINGS = "subtab_advanced_settings";
    const ID_PREFIX = "geogebra_page_component_";


    /**
     * @var int
     */
    protected static $id_counter = 0;
    /**
     * @var UploadService
     */
    protected $uploader;
    protected $pl;
    private $tpl;


    /**
     * ilSrGeogebraPluginGUI constructor
     */
    public function __construct()
    {
        parent::__construct();
        GLOBAL $DIC;
        /** @var ilComponentFactory $component_factory */
        $component_factory = $DIC["component.factory"];
        $this->pl = $component_factory->getPlugin('srgg');
        $this->uploader = new UploadService();
    }



    /**
     * @inheritDoc
     */
    public function executeCommand():void
    {
        $next_class = self::dic()->ctrl()->getNextClass($this);

        switch (strtolower($next_class)) {
            default:
                $cmd = self::dic()->ctrl()->getCmd();

                switch ($cmd) {
                    case self::CMD_CANCEL:
                    case self::CMD_CREATE:
                    case self::CMD_EDIT:
                    case self::CMD_EDIT_ADVANCED:
                    case self::CMD_INSERT:
                    case self::CMD_UPDATE:
                    case self::CMD_UPDATE_ADVANCED_PROPERTIES:
                        $this->{$cmd}();
                        break;

                    default:
                        break;
                }
                break;
        }
    }


    /**
     * @param string $properties
     *
     * @return ilPropertyFormGUI
     */
    protected function getForm($properties = "") : ilPropertyFormGUI
    {
        if (empty($properties)) {
            $form = new GeogebraFormGUI($this);
        } else {
            $form = new GeogebraFormGUI($this, $properties);
        }

        return $form;
    }


    /**
     * @inheritDoc
     */
    public function insert():void
    {
        $this->edit();
    }


    /**
     * @inheritDoc
     */
    public function create():void
    {
        $parent_id = ilObject::_lookupObjectId(filter_input(INPUT_GET, "ref_id"));
        $page_id = filter_input(INPUT_GET, "obj_id");
        $form = $this->getForm();
        $form->setValuesByPost();

        if (!$form->checkInput()) {
            self::output()->output($form);

            return;
        }

        if (!$this->uploader->uploadAllowed()) {
            ilUtil::sendFailure(self::plugin()->translate("form_upload_error"), true);
            $this->returnToParent();
            return;
        }

        $file_name = $this->uploader->handleUpload($form, $_FILES["file"]["name"], $page_id, $parent_id);

        $properties = [
            "title" => $_POST["title"],
            "legacyFileName" => $file_name,
            "fileName"       => $file_name,
        ];

        $properties = $this->mergeCustomSettings($properties);
        $properties = $this->mergeAdvancedSettings($properties);

        $this->createElement($properties);
        $this->returnToParent();
    }


    protected function mergeCustomSettings(&$properties) {
        $immutable_fields = Repository::getInstance()->getValue(ConfigAdvancedGeogebraFormGUI::KEY_IMMUTABLE);
        $customSettings = Repository::CUSTOM_SETTINGS;
        $formatedCustomSettings = [];

        foreach ($customSettings as $custom_setting) {
            $key = str_replace("custom_", "", $custom_setting);
            $config_key = sprintf("default_%s", $key);

            // If field is immutable, use the value from the config instead
            if (in_array($config_key, $immutable_fields)) {
                $formatedCustomSettings[$custom_setting] = Repository::getInstance()->getValue($config_key);
            } else {
                // If field is not set as immutable, normally use the POST value
                $formatedCustomSettings[$custom_setting] = $_POST[$key];
            }
        }

        return array_merge($properties, $formatedCustomSettings);
    }


    protected function mergeAdvancedSettings(&$properties) {
        $occurringValues = Repository::getInstance()->getFields();
        $advancedSettings = [];

        foreach ($occurringValues as $key => $occurring_value) {
            // No need to check for immutable fields, all advanced config options are used anyway
            // This if-statement just makes sure, the immutability field isn't used as an advanced option
            if ($key !== ConfigAdvancedGeogebraFormGUI::KEY_IMMUTABLE) {
                if (strpos($key, "default_") !== 0) {
                    $value = Repository::getInstance()->getValue($key);
                    $advancedSettings["advanced_" . $key] = $value;
                }
            }
        }

        return array_merge($properties, $advancedSettings);
    }


    /**
     * @inheritDoc
     */
    public function edit():void
    {
        if (!empty($this->getProperties())) {
            $this->setSubTabs(self::SUBTAB_GENERIC_SETTINGS);
        }

        $form = $this->getForm($this->getProperties());

        self::output()->output($form);
    }


    public function editAdvanced() {
        $this->setSubTabs(self::SUBTAB_ADVANCED_SETTINGS);
        $form = new SettingsAdvancedGeogebraFormGUI($this, $this->getProperties());

        self::output()->output($form);
    }


    /**
     *
     */
    public function update()/*:void*/
    {
        $parent_id = ilObject::_lookupObjectId(filter_input(INPUT_GET, "ref_id"));
        $page_id = filter_input(INPUT_GET, "obj_id");
        $properties = $this->getProperties();
        $form = $this->getForm($properties);
        $form->setValuesByPost();

        if (!$form->checkInput()) {
            self::output()->output($form);

            return;
        }

        if (!empty($_FILES["file"]["name"])) {
            if (!$this->uploader->uploadAllowed()) {
               // ilUtil::sendFailure(self::plugin()->translate("form_upload_error"), true);
                $this->tpl->setOnScreenMessage("success", ilSrGeogebraPlugin::getInstance()->txt("form_upload_error"), true);
                $this->returnToParent();
                return;
            }

            $fileName = $this->uploader->handleUpload($form, $_FILES["file"]["name"], $page_id, $parent_id);

            $properties["legacyFileName"] = $fileName;
            $properties["fileName"] = $fileName;
        }

        $properties["title"] = $_POST["title"];
        $this->updateElement($properties);

        $this->updateCustomProperties();
        $this->returnToParent();
    }


    /**
     *
     */
    public function cancel()/*:void*/
    {
        $this->returnToParent();
    }


    /**
     * @throws ilCtrlException
     */
    protected function setSubTabs($active) {
        self::dic()->tabs()->addSubTab(
            self::SUBTAB_GENERIC_SETTINGS,
            $this->pl->txt(self::SUBTAB_GENERIC_SETTINGS),
            self::dic()->ctrl()->getLinkTarget($this, self::CMD_EDIT)
        );
        self::dic()->tabs()->addSubTab(
            self::SUBTAB_ADVANCED_SETTINGS,
            $this->pl->txt(self::SUBTAB_ADVANCED_SETTINGS),
            self::dic()->ctrl()->getLinkTarget($this, self::CMD_EDIT_ADVANCED)
        );
        self::dic()->tabs()->setSubTabActive($active);
    }


    protected function updateCustomProperties() {
        $immutable_fields = Repository::getInstance()->getValue(ConfigAdvancedGeogebraFormGUI::KEY_IMMUTABLE);
        $existing_properties = $this->getProperties();
        $all_custom_properties = Repository::CUSTOM_SETTINGS;

        foreach ($existing_properties as $key => $existing_property) {
            // Only change value if mutable
            if (!in_array(str_replace("custom_", "default_", $key), $immutable_fields)) {
                if (strpos($key, "custom_") === 0) {
                    unset($all_custom_properties[$key]);
                    $postKey = str_replace("custom_", "", $key);
                    $existing_properties[$key] = $_POST[$postKey];
                }
            }
        }

        // Add remaining, newly added properties
        foreach ($all_custom_properties as $key) {
            // Only change value if mutable
            if (!in_array(str_replace("custom_", "default_", $key), $immutable_fields)) {
                if (strpos($key, "custom_") === 0) {
                    $postKey = str_replace("custom_", "", $key);
                    $existing_properties[$key] = $_POST[$postKey];
                }
            }
        }

        $this->updateElement($existing_properties);
    }


    protected function updateAdvancedProperties(): void
    {
        $immutable_fields = Repository::getInstance()->getValue(ConfigAdvancedGeogebraFormGUI::KEY_IMMUTABLE);
        $existing_properties = $this->getProperties();

        foreach ($existing_properties as $key => $existing_property) {
            // Only change value if mutable
            if (!in_array(str_replace("advanced_", "", $key), $immutable_fields)) {
                if (strpos($key, "advanced_") === 0) {
                    $postKey = str_replace("advanced_", "", $key);
                    $existing_properties[$key] = $_POST[$postKey];
                }
            }
        }

        $this->updateElement($existing_properties);
        $this->editAdvanced();
    }


    protected function convertValueByType($type, $value) {
        if ($type === Config::TYPE_INTEGER) {
            return intval($value);
        } else if ($type === Config::TYPE_DOUBLE) {
            return doubleval($value);
        } else if ($type === Config::TYPE_BOOLEAN) {
            return boolval($value);
        }

        return $value;
    }


    protected function fetchCustomFieldTypes($field_name) {
        switch ($field_name) {
            case "width":
            case "height":
                return Config::TYPE_INTEGER;
                break;
            case "enableShiftDragZoom":
            case "showResetIcon":
                return Config::TYPE_BOOLEAN;
                break;
        }

        return Config::TYPE_STRING;
    }


    protected function convertPropertyValueTypes(&$properties) {
        foreach ($properties as $key => $property) {
            if (strpos($key, "custom_") == 0) {
                $postKey = str_replace("custom_", "", $key);
                $field_type = $this->fetchCustomFieldTypes($postKey);
                $properties[$key] = $this->convertValueByType($field_type, $property);
            }

            if (strpos($key, "advanced_") == 0) {
                $postKey = str_replace("advanced_", "", $key);
               if (isset(Repository::getInstance()->getFields()[$postKey][0])){ $field_type = Repository::getInstance()->getFields()[$postKey][0];
                $properties[$key] = $this->convertValueByType($field_type, $property);
            }
        }}
    }


    protected function calculateScalingHeight(&$properties_after_change) {
        $scaling_height = $properties_after_change["custom_height"];
        $scale_factor = $properties_after_change["advanced_scale"];

        if (!is_null($scale_factor) && $scale_factor < floatval(1)) {
            // Force left alignment
            $properties_after_change["custom_alignment"] = GeogebraFormGUI::ALIGNMENT_LEFT;

            $scaling_height *= $scale_factor;
        }

        return $scaling_height . "px";
    }


    /**
     * @inheritDoc
     */
    public function getElementHTML(/*string*/ $a_mode, array $a_properties, /*string*/ $plugin_version) : string
    {
        self::$id_counter += 1;
        $id = self::ID_PREFIX . self::$id_counter;
        $plugin_dir = $this->pl->getDirectory();
        $file_name = ILIAS_WEB_DIR . '/' . CLIENT_ID . '/' . UploadService::DATA_FOLDER . '/' . $a_properties["fileName"];

        // Override properties with forced, immutable config fields
        $transformer = new GlobalConfigTransformer();
        $transformer->transformProperties($a_properties);

        // Adjust scaling dimensions so whitespaces don't appear
        $scale_height = $this->calculateScalingHeight($a_properties);

        if (!empty($iframe_id = filter_input(INPUT_GET, "iframe"))) {
            if ($iframe_id === $id) {
                $tpl = $template = self::plugin()->template("tpl.geogebra_iframe.html");
                $tpl->setVariable("ID", $id);

                // $a_properties value types need to be converted here as values only get saved as strings
                $this->convertPropertyValueTypes($a_properties);

                $tpl->setVariable("SCALE_WRAPPER_HEIGHT", $scale_height);

                // Align
                $raw_alignment = $a_properties["custom_alignment"];
                $alignment = is_null($raw_alignment) || empty($raw_alignment) ? GeogebraFormGUI::DEFAULT_ALIGNMENT : $raw_alignment;
                $tpl->setVariable("ALIGNMENT", $alignment);

                $tpl->setVariable("PLUGIN_DIR", $plugin_dir);
                $tpl->setVariable("FILE_NAME", $file_name);
                $tpl->setVariable("PROPERTIES", json_encode($a_properties));

                echo $tpl->get();
                die();
            } else {
                // Not current properties, check next
                return " ";
            }
        } else {
            $tpl = $template = self::plugin()->template("tpl.geogebra.html");
            $tpl->setVariable("ID", $id);

            if(isset($_SERVER["REQUEST_URI"])) {
                $tpl->setVariable("URL", filter_input(INPUT_SERVER, "REQUEST_URI") . '&iframe=' . $id);
            }

            $tpl->setVariable("SCALE_WRAPPER_HEIGHT", $scale_height);

            return $tpl->get();
        }
    }
}