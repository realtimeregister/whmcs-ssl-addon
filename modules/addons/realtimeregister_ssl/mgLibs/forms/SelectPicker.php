<?php

namespace MGModule\RealtimeRegisterSsl\mgLibs\forms;
use MGModule\RealtimeRegisterSsl as main;


/**
 * Select Form Field
 *
 * @SuppressWarnings(PHPMD)
 */
class SelectPicker extends AbstractField
{
    public $translateOptions            = true;
    public $addValueIfNotExits          = false;
    public $options;
    
    function prepare()
    {
        $this->type = 'selectPicker';
        if (
            $this->translateOptions && array_keys($this->options) == range(0, count($this->options) - 1)
        ) {
            $options = [];
            foreach($this->options as $value) {
                $options[$value] = $value;
            }
            $this->options = $options;
        } else {
            $this->translateOptions = false;
        }
        
        if ($this->addValueIfNotExits) {
            if ($this->value && !isset($this->options[$this->value])) {
                $this->options[$this->value] = $this->value;
            }
        }
        
        if ($this->translateOptions) {
            if (!is_array($this->options)) {
                throw new main\mgLibs\exceptions\System('Invalid Fields Options');
            }
            $options = [];
            foreach($this->options as $value) {
                $options[$value] = main\mgLibs\Lang::T($this->formName,$this->name,'options',$value); 
            }
            $this->options = $options;
        }
    }
}
