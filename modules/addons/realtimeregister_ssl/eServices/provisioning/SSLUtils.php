<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use RealtimeRegister\Domain\Product;

trait SSLUtils
{
    public function getSansLimit(array $params)
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

    public function getSansLimitWildcard(array $params)
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

    public function getSanDomainCount(array $sanDomains, string $commonName, $productBrand): int
    {
        $count = 0;
        foreach ($sanDomains as $domain) {
            if (in_array('WWW_INCLUDED', $productBrand->features) && str_starts_with($domain, 'www.')) {
                $normalizedDomain = preg_replace('/^www\./', '', $domain);
            } elseif (in_array('NON_WWW_INCLUDED', $productBrand->features) && !str_starts_with($domain, 'www.')) {
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

    /**
     * @throws \Exception
     */
    public function mapRequestFields(array $params, Product $product) : array {
        $mapping = [
            'organization' => 'orgname',
            'country' => 'country',
            'state' => 'state',
            'address' => 'address1',
            'postalCode' => 'postcode',
            'city' => 'city',
            'saEmail' => 'email'
        ];

        $orgMapping = [
            'organization' => 'org_name',
            'country' => 'org_country',
            'state' => 'org_regions',
            'address' => 'org_addressline1',
            'postalCode' => 'org_postalcode',
            'city' => 'org_city',
            'coc' => 'org_coc'
        ];

        $orgFields = ((array) $params['fields']) ?? [];
        $order = [];

        foreach (array_merge($product->requiredFields ?? [], $product->optionalFields ?? []) as $value) {
            if ($value === 'approver') {
                $order['approver'] = [
                    'firstName' => $params['firstname'],
                    'lastName' => $params['lastname'],
                    'jobTitle' => $params['jobtitle'] ?: null,
                    'email' => $params['email'],
                    'voice' => preg_replace('/\r|\n|\s/', '', $params['phonenumber'])
                ];
            } else {
                $order[$value] = $orgFields[$orgMapping[$value]] ?: $params[$mapping[$value]];
            }
        }

        if (strlen($order['country'] > 2)) {
            $order['country'] = Countries::getInstance()
                ->getCountryCodeByName($order['country']);
        }

        return $order;
    }
}