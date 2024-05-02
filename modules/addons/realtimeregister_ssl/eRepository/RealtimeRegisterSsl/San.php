<?php

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use MGModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use MGModule\RealtimeRegisterSsl\mgLibs\Lang;

class San
{
    /**
     * Types:
     * 
     * * text
     * * password
     * * yesno
     * * dropdown
     * * radio
     * * textarea
     */
    public static function getTitle()
    {
        return Lang::getInstance()->T('sansTitle');
    }

    public static function getFields($limit, $limitwildcard = 0, $params)
    {
        $sanEnabledForWHMCSProduct = $params[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        $sanWildcardEnabledForWHMCSProduct = $params[ConfigOptions::PRODUCT_ENABLE_SAN_WILDCARD] === 'on';

        $fields                 = [];

        if ($sanEnabledForWHMCSProduct == 'on' && $limit > 0) {
            $fields['sans_domains'] = [
                'FriendlyName' => Lang::getInstance()->T('sansFreindlyName') . sprintf(' (%s)', $limit),
                'Type' => 'textarea',
                'Size' => '30',
                'Description' => '<br>' . Lang::getInstance()->T('sansDescription'),
                'Required' => false,

            ];
        }
        if ($sanWildcardEnabledForWHMCSProduct == 'on' && $limitwildcard > 0) {
            $fields['wildcard_san'] = [
                'FriendlyName' => Lang::getInstance()->T('wildcardSansFreindlyName') . sprintf(' (%s)', $limitwildcard),
                'Type' => 'textarea',
                'Size' => '30',
                'Description' => '<br>' . Lang::getInstance()->T('sansDescription'),
                'Required' => false,

            ];
        }
        return $fields;
    }
}
