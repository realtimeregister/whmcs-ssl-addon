<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

class SSLUtils
{
    public static function getSansLimit(array $params)
    {
        $sanEnabledForWHMCSProduct = $params[ConfigOptions::PRODUCT_ENABLE_SAN] === 'on';
        if (!$sanEnabledForWHMCSProduct) {
            return 0;
        }
        $period = intval($params['configoptions'][ConfigOptions::OPTION_PERIOD][0]);
        $includedSans = (int)$params[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans = (int)$params['configoptions'][ConfigOptions::OPTION_SANS_COUNT . $period];
        return $includedSans + $boughtSans;
    }

    public static function getSansLimitWildcard(array $params)
    {
        $sanEnabledForWHMCSProduct = $params[ConfigOptions::PRODUCT_ENABLE_SAN_WILDCARD] === 'on';
        if (!$sanEnabledForWHMCSProduct) {
            return 0;
        }
        $period = intval($params['configoptions'][ConfigOptions::OPTION_PERIOD][0]);
        $includedSans = (int)$params[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSans = (int)$params['configoptions'][ConfigOptions::OPTION_SANS_WILDCARD_COUNT . $period];
        return $includedSans + $boughtSans;
    }

    public static function getSanDomainCount(array $sanDomains, string $commonName, $productBrand): int
    {
        $count = 0;
        foreach ($sanDomains as $domain) {
            if (in_array('WWW_INCLUDED', $productBrand->features) && str_starts_with($domain, 'www.')) {
                $normalizedDomain = preg_replace('/^www\./', '', $domain);
            } elseif (in_array('NON_WWW_INCLUDED', $productBrand->features)
                && !str_starts_with($domain, 'www.')) {
                $normalizedDomain = 'www.' . $domain;
            } else {
                $normalizedDomain = $domain;
            }

            if ($normalizedDomain !== $commonName) {
                $count++;
            }
        }
        return $count;
    }
}