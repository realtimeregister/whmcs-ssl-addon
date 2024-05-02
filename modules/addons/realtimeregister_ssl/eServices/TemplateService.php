<?php

namespace MGModule\RealtimeRegisterSsl\eServices;

class TemplateService
{
    public static function buildTemplate($template, array $vars = [])
    {
        \MGModule\RealtimeRegisterSsl\Addon::I(true);
        $dir = \MGModule\RealtimeRegisterSsl\Addon::getModuleTemplatesDir();
        return \MGModule\RealtimeRegisterSsl\mgLibs\Smarty::I()->view($dir . '/' . $template, $vars);
        $path = $dir . '/' . $template;
        $path = str_replace('\\', '/', $path);
        return \MGModule\RealtimeRegisterSsl\mgLibs\Smarty::I()->view($path, $vars);
    }
}
