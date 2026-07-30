<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\server\clientarea\Traits;

use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions as C;
use RealtimeRegister\Api\AcmeApi;

;

trait AcmeTrait {

    /**
     * @throws System
     */
    private function acmeIndex($input, $product, $sslService, $vars = [])
    {
//        $subscriptionRepo = new \MGModule\SSLCENTERWHMCS\models\acmeSubscription\Repository();
//        $domainRepo       = new \MGModule\SSLCENTERWHMCS\models\acmeSubscriptionDomain\Repository();
//
//        $subscription = $subscriptionRepo->getByServiceId($serviceId);
//        if ($subscription && (int) $subscription->api_order_id > 0 && strtolower((string) $subscription->status) !== 'active')
//        {
//            try
//            {
//                $this->refreshAcmeCertificateDetails($serviceId);
//                $subscription = $subscriptionRepo->getByServiceId($serviceId);
//            }
//            catch (\Exception $e)
//            {
//                main\eHelpers\Whmcs::savelogActivitySSLCenter(
//                    'SSLCENTER WHMCS: Unable to auto-refresh ACME certificate details for service #' . (int) $serviceId . '. Error: ' . $e->getMessage()
//                );
//            }
//        }
//        $domains      = $domainRepo->getByServiceId($serviceId);
//        $limits = $this->getAcmeDomainLimits($input['params']);
//        $singleIncludedSans = $this->getAcmeIncludedSans($input['params'], 'single');
//        $wildcardIncludedSans = $this->getAcmeIncludedSans($input['params'], 'wildcard');
//        $singleBoughtSans = isset($input['params']['configoptions']['sans_count']) ? (int) $input['params']['configoptions']['sans_count'] : 0;
//        $wildcardBoughtSans = isset($input['params']['configoptions']['sans_wildcard_count']) ? (int) $input['params']['configoptions']['sans_wildcard_count'] : 0;
//        $activeCounts = $this->getActiveAcmeDomainsCount($serviceId, $domainRepo->tableName);
//        $singleSansCurrent = $activeCounts['single'];
//        $wildcardSansCurrent = $activeCounts['wildcard'];
//        $totalSansCurrent = $singleSansCurrent + $wildcardSansCurrent;
//        $singleSansPurchased = $singleIncludedSans + $singleBoughtSans;
//        $wildcardSansPurchased = $wildcardIncludedSans + $wildcardBoughtSans;
//        $totalSansPurchased = $singleSansPurchased + $wildcardSansPurchased;
//        $availableSingleSlots = max(0, $limits['single'] - $singleSansCurrent);
//        $availableWildcardSlots = max(0, $limits['wildcard'] - $wildcardSansCurrent);
//        $canAddDomains = $availableSingleSlots > 0 || ($availableWildcardSlots > 0 && isset($product->configoption13) && $product->configoption13 === 'on');
//
//        $vars['allOk']                = true;
//        $vars['serviceid']            = $serviceId;
//        $vars['userid']               = $userId;
//        $vars['isAcmeSubscription']   = true;
//        $vars['productName']          = isset($product->name) ? $product->name : main\mgLibs\Lang::absoluteT('acmeDefaultProductName');
//        $vars['allowWildcard']        = isset($product->configoption13) && $product->configoption13 === 'on';
//        $vars['subscription']         = $subscription;
//        $vars['domains']              = $domains;
//        $vars['singleSansCurrent']    = $singleSansCurrent;
//        $vars['singleSansPurchased']  = $singleSansPurchased;
//        $vars['wildcardSansCurrent']  = $wildcardSansCurrent;
//        $vars['wildcardSansPurchased']= $wildcardSansPurchased;
//        $vars['totalSansCurrent']     = $totalSansCurrent;
//        $vars['totalSansPurchased']   = $totalSansPurchased;
//        $vars['availableSingleSlots'] = $availableSingleSlots;
//        $vars['availableWildcardSlots'] = $availableWildcardSlots;
//        $vars['canAddDomains']        = $canAddDomains;
//        $vars['configurationStatus']  = $sslService ? $sslService->status : null;
//        $vars['configurationURL']     = 'clientarea.php?action=productdetails&id=' . $serviceId . '&acmeconfig=1';
//        $vars['nextInvoiceDate']      = '';
//
//        if ($subscription && (int) $subscription->auto_renew === 1 && !empty($subscription->renewal_date))
//        {
//            $apiConf = (new \MGModule\SSLCENTERWHMCS\models\apiConfiguration\Repository())->get();
//            $renewInvoiceDays = isset($apiConf->renew_invoice_days_subscription) && is_numeric($apiConf->renew_invoice_days_subscription)
//                ? (int) $apiConf->renew_invoice_days_subscription
//                : 0;
//            if ($renewInvoiceDays < 0) {
//                $renewInvoiceDays = 0;
//            }
//
//            $renewalTimestamp = strtotime((string) $subscription->renewal_date);
//            if ($renewalTimestamp !== false) {
//                $vars['nextInvoiceDate'] = date('Y-m-d', strtotime('-' . $renewInvoiceDays . ' days', $renewalTimestamp));
//            }
//        }
//
//        return array(
//            'tpl' => 'home_acme',
//            'vars' => $vars
//        );

    }

    public function acmeConfiguration($input, $product, $vars): array
    {
        $serviceId = (int) $input['params']['serviceid'];
        $userId    = (int) $input['params']['userid'];

        $singleLimit = isset($input['params']['configoptions']['sans_count']) ? (int) $input['params']['configoptions']['sans_count'] : 0;
        $wildcardLimit = isset($input['params']['configoptions']['sans_wildcard_count']) ? (int) $input['params']['configoptions']['sans_wildcard_count'] : 0;

        $vars['serviceid']          = $serviceId;
        $vars['userid']             = $userId;
        $vars['productName']        = $product->name;
        $vars['singleDomainsLimit'] = $singleLimit;
        $vars['wildcardDomainsLimit'] = $wildcardLimit;

        return [
            'tpl' => 'acme_configuration',
            'vars' => $vars
        ];
    }

    public static function isAcmeProduct($product): bool {
        return str_contains($product->{C::API_PRODUCT_ID}, 'acme');
    }

    public function createSubscriptionJSON($input) {
        $domains = $input['domains'] ?? [];

        $singleLimit   = isset($input['params']['configoptions']['sans_count'])
            ? (int) $input['params']['configoptions']['sans_count']
            : 0;
        $wildcardLimit = isset($input['params']['configoptions']['sans_wildcard_count'])
            ? (int) $input['params']['configoptions']['sans_wildcard_count']
            : 0;

        $singleDomains   = array_values(array_filter($domains, fn($d) => !str_starts_with($d, '*.')));
        $wildcardDomains = array_values(array_filter($domains, fn($d) => str_starts_with($d, '*.')));

        if (count($singleDomains) > $singleLimit) {
            throw new \InvalidArgumentException(
                sprintf('Number of domains (%d) exceeds the allowed limit (%d).', count($singleDomains), $singleLimit)
            );
        }

        if (count($wildcardDomains) > $wildcardLimit) {
            throw new \InvalidArgumentException(
                sprintf('Number of wildcard domains (%d) exceeds the allowed limit (%d).', count($wildcardDomains), $wildcardLimit)
            );
        }

        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);
        $customer = ApiProvider::getInstance()::getCustomer();
        $product  = $input['params'][C::API_PRODUCT_ID];
        $period   = $this->parsePeriod($input['params']['model']->billingcycle);

        return $api->create(
            $customer,
            $product,
            $period,
            $domains
        )->toArray();
    }
}
