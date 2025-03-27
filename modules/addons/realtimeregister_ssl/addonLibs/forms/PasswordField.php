<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\forms;

class PasswordField extends AbstractField
{
    public bool $showPassword = false;
    public $type = 'password';

    public static function obfuscateValue(): string
    {
        return str_repeat('*', 32);
    }

    public function prepare(): void
    {
        if (!$this->showPassword) {
            $this->value = self::obfuscateValue();
        }
    }
}
