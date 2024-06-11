<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Client;

class Api extends Client
{
    public function __construct()
    {
        $config = AddonModules::select(['value', 'setting'])->addon()->get();

        $args = [];

        foreach ($config as $setting) {
            $args[$setting->setting] = $setting->value;
        }

        parent::__construct($args);
    }
}
