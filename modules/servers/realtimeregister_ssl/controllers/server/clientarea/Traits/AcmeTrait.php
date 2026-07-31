<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\server\clientarea\Traits;

use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions as C;
use RealtimeRegister\Api\AcmeApi;
use RealtimeRegister\Domain\Enum\AcmeSubscriptionStatusEnum;

;

trait AcmeTrait {

    /**
     * @param $input
     * @param mixed $domains
     * @return mixed
     */
    private function validateDomainLimits($input, mixed $domains): mixed
    {
        //TODO (NON_)WWW_INCLUDED
        if (count($domains) == 0) {
            throw new \InvalidArgumentException('No domains provided');
        }

        list($singleLimit, $wildcardLimit) = $this->getDomainLimits($input);

        $singleDomains = array_values(array_filter($domains, fn($d) => !str_starts_with($d, '*.')));
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
        return $input;
    }

    /**
     * @param $input
     * @return array
     */
    public function getDomainLimits($input): array
    {
        $singleLimit = isset($input['params']['configoptions']['sans_count'])
            ? (int)$input['params']['configoptions']['sans_count']
            : 0;
        $wildcardLimit = isset($input['params']['configoptions']['sans_wildcard_count'])
            ? (int)$input['params']['configoptions']['sans_wildcard_count']
            : 0;
        return [$singleLimit, $wildcardLimit];
    }

    /**
     * @throws System
     * @throws \Exception
     */
    private function acmeIndex($input, $product, SSL $sslService, $vars = [])
    {
        $domains = $sslService->getDomains();
        list($domainLimits, $wildcardLimits) = $this->getDomainLimits($input);

        $vars['serviceid'] = $input['params']['serviceid'];
        $vars['userid'] = $sslService->userid;
        $vars['productName'] = $product->name;
        $vars['validTill'] = self::formatDate($sslService->getValidTill()->date);
        $vars['domains'] = $domains;
        $vars['domainCount'] = count(array_filter($domains, fn($domain) => !str_starts_with($domain, '*.')));
        $vars['wildcardDomainCount'] = count(array_filter($domains, fn($domain) => str_starts_with($domain, '*.')));
        $vars['domainLimits'] = $domainLimits;
        $vars['wildcardLimits'] = $wildcardLimits;
        $vars['configurationStatus'] = $sslService->status;
        $vars['nextInvoiceDate'] = '';
        $vars['directoryUrl'] = $sslService->getDirectoryUrl();

        return [
            'tpl' => 'home_acme',
            'vars' => $vars
        ];

    }

    public function acmeConfiguration($input, $product, $vars): array
    {
        $serviceId = (int) $input['params']['serviceid'];
        $userId    = (int) $input['params']['userid'];

        list($singleLimit, $wildcardLimit) = $this->getDomainLimits($input);

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

        $input = $this->validateDomainLimits($input, $domains);

        /**
         * @var AcmeApi $api
         */
        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);
        $customer = ApiProvider::getInstance()::getCustomer();
        $product  = $input['params'][C::API_PRODUCT_ID];
        $period   = $this->parsePeriod($input['params']['model']->billingcycle);
        $serviceId = $input['params']['serviceid'];
        $sslService = SSL::getByServiceId($serviceId);

        $response = $api->create(
            customer: $customer,
            product: $product,
            period: $period,
            domainNames: $domains,
            autoRenew: false // Let WHMCS handle renewals
        );

        $sslService->setRemoteId($response->id);
        $sslService->setAcmeCredentials($response->accountKey, $response->hmacKey);
        $sslService->setDirectoryUrl($response->directoryUrl);
        $sslService->setStatus(SSL::ACTIVE); //TODO PENDING VALIDATION OV/EV
        $sslService->setSSLStatus(AcmeSubscriptionStatusEnum::ACTIVE);
        $sslService->save();
        $this->updateAcmeConfigData($sslService);
    }

    /**
     * @throws \Exception
     */
    public function addDomainsJSON(array $input) : void {
        $domains = $input['domains'] ?? [];



        $serviceId  = $input['params']['serviceid'];
        $sslService = SSL::getByServiceId($serviceId);
        $remoteId   = $sslService->getRemoteId();

        /** @var AcmeApi $api */
        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);

        $acmeSubscription = $api->get($remoteId);
        $currentDomains = $acmeSubscription->domainNames;

        //TODO (NON_)WWW_INCLUDED
        $newDomains = array_unique(array_merge($acmeSubscription->domainNames, $domains));

        $this->validateDomainLimits($input, $newDomains);

        $api->update(
            acmeSubscriptionId: $remoteId,
            domainNames: $newDomains
        );

        $this->updateAcmeConfigData($sslService);
    }

    /**
     * @throws \Exception
     */
    public function removeDomainJSON(array $input) : void {
        $domain = $input['domain'] ?? null;

        if (empty($domain)) {
            throw new \InvalidArgumentException('No domain provided');
        }

        $serviceId  = $input['params']['serviceid'];
        $sslService = SSL::getByServiceId($serviceId);
        $remoteId   = $sslService->getRemoteId();

        /** @var AcmeApi $api */
        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);
        $acmeSubscription = $api->get($remoteId);

        $newDomains = array_values(array_filter($acmeSubscription->domainNames, fn($d) => $d !== $domain));
        if (empty($newDomains)) {
            throw new \InvalidArgumentException('Subscription needs at least one domain');
        }

        $api->update(
            acmeSubscriptionId: $remoteId,
            domainNames: $newDomains
        );

        $this->updateAcmeConfigData($sslService);
    }

    /**
     * @throws \Exception
     */
    public function showCredentialsJSON(array $input): array
    {
        $type = $input['type'] ?? null;
        $serviceId  = $input['params']['serviceid'];
        $sslService = SSL::getByServiceId($serviceId);
        if ($type === 'accountKey') {
            return ['accountKey' => $sslService->getAccountKey()];
        } else if ($type === 'hmacKey') {
            return ['hmacKey' => $sslService->getHmacKey()];
        }

        return ['accountKey' => $sslService->getAccountKey(), 'hmacKey' => $sslService->getHmacKey()];
    }

    public function updateAcmeConfigData(SSL $sslService) {
        $remoteId = $sslService->getRemoteId();
        /** @var AcmeApi $api */
        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);
        $acmeSubscription = $api->get($remoteId);
        $domains = $acmeSubscription->domainNames;
        $sslService->setDomains($domains);
        $sslService->setValidTill($acmeSubscription->expiryDate);
        $sslService->save();
    }
}
