<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\forms;
use AddonModule\RealtimeRegisterSsl as main;

/**
 * CheckBox Form Field
 *
 * @SuppressWarnings(PHPMD)
 */
class CheckboxField extends AbstractField
{
    public $translateOptions = true;
    public $options;
    public $type             = 'checkbox';
    private $prepared = false;
    public $inline = false;
    
    
    public function prepare(): void
    {
        if ($this->prepared) {
            return;
        }
        
        $this->prepared = true;
        if (array_keys($this->options) == range(0, count($this->options) - 1)) {
            $options = [];
            foreach($this->options as $value) {
                $options[$value] = $value;
            }
            $this->options = $options;
        } else {
            $this->translateOptions = false;
        }
        
        if ($this->translateOptions) {
            $options = [];
            foreach($this->options as $key => $value) {
                $options[$value] = main\addonLibs\Lang::T($this->name,'',$value); 
            }
            $this->options = $options;
        }
    }
}
