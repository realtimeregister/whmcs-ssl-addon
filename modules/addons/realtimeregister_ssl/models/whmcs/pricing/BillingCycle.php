<?php

/* * ********************************************************************
 * SSLCENTERWHMCS product developed. (2015-12-08)
 * *
 *
 *  CREATED BY MODULESGARDEN       ->       http://modulesgarden.com
 *  CONTACT                        ->       contact@modulesgarden.com
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */

namespace MGModule\RealtimeRegisterSsl\models\whmcs\pricing;

/**
 * Description of BillingCycle
 *
 * @author Pawel Kopec <pawelk@modulesgarden.com>
 */
class BillingCycle
{
    //Product and Addons
    public const FREE          = 'free';
    public const ONE_TIME      = 'onetime';
    public const MONTHLY       = 'monthly';
    public const QUARTERLY     = 'quarterly';
    public const SEMI_ANNUALLY = 'semiannually';
    public const ANNUALLY      = 'annually';
    public const BIENNIALLY    = 'biennially';
    public const TRIENNIALLY   = 'triennially';
    //Domains
    public const YEAR     = 'YEAR';
    public const YEARS_2  = 'YEARS_2';
    public const YEARS_3  = 'YEARS_3';
    public const YEARS_4  = 'YEARS_4';
    public const YEARS_5  = 'YEARS_5';
    public const YEARS_6  = 'YEARS_6';
    public const YEARS_7  = 'YEARS_7';
    public const YEARS_8  = 'YEARS_8';
    public const YEARS_9  = 'YEARS_9';
    public const YEARS_10 = 'YEARS_10';
    public const PERIODS = [
        'free account' => 'free',
        'monthly'      => '1',
        'quarterly'    => '3',
        'semiannually' => '6',
        'annually'     => '12',
        'biennially'   => '24',
        'triennially'  => '36',
    ];

    public static function convertPeriodToString($period)
    {
        if ($period == 1) {
            return 'YEAR';
        }

        if ($period > 1 && $period <= 10) {
            return 'YEARS_' . $period;
        }

        throw new \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\System('Invalid period: ' . $period);
    }

    public static function convertStringToPeriod($string)
    {
        $string = strtolower($string);
        if (key_exists($string, self::PERIODS)) {
            return self::PERIODS[$string];
        }

        throw new \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\System('Invalid period: ' . $string);
    }

    public static function convertPeriodToName($period)
    {
        if ($key = array_search($period, self::PERIODS)) {
            return $key;
        }

        throw new \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\System('Invalid period: ' . $period);
    }
}
