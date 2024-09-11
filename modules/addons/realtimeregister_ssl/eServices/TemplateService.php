<?php

namespace AddonModule\RealtimeRegisterSsl\eServices;

class TemplateService
{
    public static function buildTemplate($template, array $vars = [])
    {
        \AddonModule\RealtimeRegisterSsl\Addon::I(true);
        $dir = \AddonModule\RealtimeRegisterSsl\Addon::getModuleTemplatesDir();
        return \AddonModule\RealtimeRegisterSsl\mgLibs\Smarty::I()->view($dir . '/' . $template, $vars);
        $path = $dir . '/' . $template;
        $path = str_replace('\\', '/', $path);
        return \AddonModule\RealtimeRegisterSsl\mgLibs\Smarty::I()->view($path, $vars);
    }
}
