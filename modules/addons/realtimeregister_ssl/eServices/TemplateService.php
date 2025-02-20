<?php

namespace AddonModule\RealtimeRegisterSsl\eServices;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\addonLibs\Smarty;

class TemplateService
{
    public static function buildTemplate($template, array $vars = [])
    {
        Addon::I(true);
        $dir = Addon::getModuleTemplatesDir();
        return Smarty::I()->view($dir . '/' . $template, $vars);
    }
}
