<?php

namespace AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;

class Organization
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
    public static function getTitle() {
        return Lang::getInstance()->T('confOrganizationTitle');
    }

    public static function getFields() {
        $org                     = [];
        $org['org_name']         = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationName'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false,
        ];
        $org['org_coc']     = [
            'FriendlyName' => Lang::getInstance()->T('CoC'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
        ];
        $org['org_addressline1'] = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationAddress'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
        ];
        $org['org_city']         = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationCity'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
        ];
        $org['org_country']      = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationCountry'),
            'Type'         => 'dropdown',
            'Description'  => '',
            'Required'     => false,
            'Options'      => Countries::getInstance()->getCountriesForWhmcsDropdownOptions(),
        ];
        $org['org_postalcode']   = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationZipCode'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
        ];
        $org['org_regions']       = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationStateRegion'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
        ];
        return $org;
    }
}
