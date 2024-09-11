<?php

namespace AddonModule\RealtimeRegisterSsl\mgLibs\forms;
use AddonModule\RealtimeRegisterSsl as main;

/**
 * Abstract Form Field
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class AbstractField
{
    public $name;
    public $value;
    public $type;
    public $enableDescription = false;
    public $enableLabel = true;
    public $formName = false;
    public $default;
    public $nameAttr;
    public $addFormNameToFields = false;
    public $dataAttr = [];
    public $readonly = false;
    public $disabled = false;
    public $addIDs = false;
    public $colWidth = 9;
    public $buttonLabelColWidth =3;
    public $labelcolWidth =2;
    public $continue = false;
    public $html = '';
    public $additinalClass = false;
    public $opentag;
    public $closetag;
    public $error;
    public $id = false;
    public $required = false;
    
    function __construct($params = [])
    {
        foreach($params as $name => $value) {
            if(property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }
    }
    
    function prepare()
    {
    }
    
    function generate()
    {
        $this->prepare();
        
        if ($this->addFormNameToFields && empty($this->nameAttr)) {
            $this->nameAttr = $this->formName.'_'.$this->name;
        }
        
        if (empty($this->nameAttr)) {
            $this->nameAttr = $this->name;
        }
        
        if (empty($this->value) && !empty($this->default)) {
            $this->value = $this->default;
        }
        
        if ($this->opentag == false) {
            $this->enableLabel = false;
        }
        
        \AddonModule\RealtimeRegisterSsl\mgLibs\Lang::stagCurrentContext('generateField');
        
        if($this->type == 'submit') {
            \AddonModule\RealtimeRegisterSsl\mgLibs\Lang::addToContext($this->value);
        } else {
            \AddonModule\RealtimeRegisterSsl\mgLibs\Lang::addToContext($this->name);
        }
        
        $this->html = \AddonModule\RealtimeRegisterSsl\mgLibs\Smarty::I()->view(
            $this->type,
            (array)$this,
            \AddonModule\RealtimeRegisterSsl\mgLibs\process\MainInstance::getModuleTemplatesDir().DS.'formFields'
        );
        
        main\mgLibs\Lang::unstagContext('generateField');
    }
}
