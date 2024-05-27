<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\Client;

use MGModule\RealtimeRegisterSsl\Models\AddonModules;

/**
 * Class Client
 */
class Api extends Client
{
    /**
     * Client constructor.
     * @param array $args
     */
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
