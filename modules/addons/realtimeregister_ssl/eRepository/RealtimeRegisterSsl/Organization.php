<?php

namespace MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl;

use MGModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use MGModule\RealtimeRegisterSsl\mgLibs\Lang;

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
            'Required'     => true,
        ];
        $org['org_division']     = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationDivision'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
            
        ];
        $org['org_lei']     = [
            'FriendlyName' => Lang::getInstance()->T('LEI code'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
            
        ];
        $org['org_duns']         = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationDuns'),
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
            'Required'     => true
        ];
        $org['org_city']         = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationCity'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        $org['org_country']      = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationCountry'),
            'Type'         => 'dropdown',
            'Description'  => '',
            'Required'     => true,
            'Options'      => Countries::getInstance()->getCountriesForWhmcsDropdownOptions(),
        ];
        $org['org_fax']          = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationFax'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => false
        ];
        $org['org_phone']        = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationPhoneNumber'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        $org['org_postalcode']   = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationZipCode'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        $org['org_regions']       = [
            'FriendlyName' => Lang::getInstance()->T('confOrganizationStateRegion'),
            'Type'         => 'text',
            'Size'         => '30',
            'Description'  => '',
            'Required'     => true
        ];
        return $org;
    }
}
