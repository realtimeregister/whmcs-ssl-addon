<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\forms;

use AddonModule\RealtimeRegisterSsl as main;

/**
 * Select Form Field
 *
 * @SuppressWarnings(PHPMD)
 */
class SelectField extends AbstractField
{
    public $translateOptions = true;
    public $addValueIfNotExists = false;
    public $options;
    public $select2 = false;
    public $multiple;

    function prepare()
    {
        if ($this->select2) {
            $this->type = 'select2';
            if (empty($this->addIDs)) {
                $this->addIDs = 'RandID' . rand(0, 100);
            }
        } else {
            $this->type = 'select';
        }

        if ($this->translateOptions && array_keys($this->options) == range(0, count($this->options) - 1)) {
            $options = [];
            foreach ($this->options as $value) {
                $options[$value] = $value;
            }
            $this->options = $options;
        } else {
            $this->translateOptions = false;
        }

        if ($this->addValueIfNotExists) {
            if ($this->value && !isset($this->options[$this->value])) {
                $this->options[$this->value] = $this->value;
            }
        }

        if ($this->translateOptions) {
            if (!is_array($this->options)) {
                throw new main\addonLibs\exceptions\System('Invalid Fields Options');
            }

            $options = [];
            foreach ($this->options as $value) {
                $options[$value] = main\addonLibs\Lang::T($this->name, 'options', $value);
            }
            $this->options = $options;
        }
    }
}
