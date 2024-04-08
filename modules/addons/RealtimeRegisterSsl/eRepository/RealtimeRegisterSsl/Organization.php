<?php

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use Exception;

class Organization {

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
        return \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationTitle');
    }

    public static function getFields() {
        $org                     = [];
        $org['org_name']         = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationName'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true,
        ];
        $org['org_division']     = [
            'FriendlyName' => 'Division',
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationDivision'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
            
        ];
        $org['org_lei']     = [
            'FriendlyName' => 'LEI code',
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('LEI code'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
            
        ];
        $org['org_duns']         = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationDuns'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
        ];
        $org['org_addressline1'] = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationAddress'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        $org['org_city']         = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationCity'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        $org['org_country']      = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationCountry'),
            'Type'         => 'dropdown',
            'Description'  => '',
            'Required'     => true,
            'Options'      => \MGModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries::getInstance()->getCountriesForWhmcsDropdownOptions(),
        ];
        $org['org_fax']          = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationFax'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
        ];
        $org['org_phone']        = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationPhoneNumber'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        $org['org_postalcode']   = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationZipCode'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        $org['org_regions']       = [
            'FriendlyName' => \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance()->T('confOrganizationStateRegion'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        return $org;
    }
}
