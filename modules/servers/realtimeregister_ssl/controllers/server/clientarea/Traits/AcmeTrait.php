<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\server\clientarea\Traits;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions as C;
use DateTime;
use Exception;
use InvalidArgumentException;
use RealtimeRegister\Api\AcmeApi;
use RealtimeRegister\Domain\Approver;
use RealtimeRegister\Domain\Enum\AcmeSubscriptionStatusEnum;
use RealtimeRegister\Domain\Enum\FeatureEnum;

trait AcmeTrait
{

    private static function splitDomains(array $domains): array
    {
        $wildcardDomains = array_values(array_filter($domains, fn($domain) => str_starts_with($domain, '*.')));
        $singleDomains = array_values(array_filter(
            $domains, fn($domain) => !str_starts_with($domain, '*.')
            && !in_array("*." . $domain, $wildcardDomains)));
        return [$singleDomains, $wildcardDomains];
    }

    private static function splitCurrentDomains(array $domains): array
    {
        $wildcardDomains = array_values(array_filter($domains, fn($domain) => str_starts_with($domain->domainName, '*.')));
        $singleDomains = array_values(array_filter($domains,
            fn($domain) => !str_starts_with($domain->domainName, '*.') && $domain->isCharged));
        return [$singleDomains, $wildcardDomains];
    }

    /**
     * Validate domain limits based on product features and current domains
     *
     * @param $input
     * @param array $domains
     * @param array $currentDomains
     * @return array
     */
    private function validateDomainLimits($input, array $domains, array $currentDomains = []): array
    {
        $apiProduct = (new Products())->getProduct(KeyToIdMapping::getIdByKey($input['params'][C::API_PRODUCT_ID]));
        $features = $apiProduct->getFeatures() ?? [];

        list($singleLimit, $wildcardLimit) = $this->getDomainLimits($input);
        list($singleDomains, $wildcardDomains) = self::splitDomains($domains);
        list($currentSingleDomains, $currentWildcardDomains) = self::splitCurrentDomains($currentDomains);

        if ($currentDomains) {
            $singleDomains = array_values(array_filter(
                $domains, fn($domain) => !str_starts_with($domain, '*.')
                && !in_array("*." . $domain, $currentWildcardDomains)));

            if (in_array(FeatureEnum::FEATURE_WWW_INCLUDED, $features)) {
                $singleDomains = array_values(array_filter($singleDomains,
                    fn($domain) => !str_starts_with($domain, 'www.') || !in_array(substr($domain, 4), $currentSingleDomains)));
            }

            if (in_array(FeatureEnum::FEATURE_NON_WWW_INCLUDED, $features)) {
                $singleDomains = array_values(array_filter($singleDomains,
                    fn($domain) => str_starts_with($domain, 'www.') || !in_array("www." . $domain, $currentSingleDomains)));
            }
        }

        if (in_array(FeatureEnum::FEATURE_WWW_INCLUDED, $features)) {
            $singleDomains = array_values(array_filter($singleDomains,
                fn($domain) => !str_starts_with($domain, 'www.') || !in_array(substr($domain, 4), $singleDomains)));
        }


        $totalSingleDomains = count($singleDomains) + count($currentSingleDomains);
        $totalWildcardDomains = count($wildcardDomains) + count($currentWildcardDomains);

        if ($totalSingleDomains > $singleLimit) {
            throw new InvalidArgumentException(
                sprintf('Number of domains (%d) exceeds the allowed limit (%d).', $totalSingleDomains, $singleLimit)
            );
        }

        if ($totalWildcardDomains > $wildcardLimit) {
            throw new InvalidArgumentException(
                sprintf('Number of wildcard domains (%d) exceeds the allowed limit (%d).', $totalWildcardDomains, $wildcardLimit)
            );
        }

        return array_merge($singleDomains, $wildcardDomains);
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
     * @throws Exception
     */
    private function acmeIndex($input, $product, SSL $sslService, $vars = []) : array
    {
        $this->updateAcmeConfigData($sslService);
        $domains = $sslService->getDomains();
        list($domainLimits, $wildcardLimits) = $this->getDomainLimits($input);
        list($domainCount, $wildcardDomainCount) = $this->splitCurrentDomains($domains);


        $vars['serviceid'] = $input['params']['serviceid'];
        $vars['userid'] = $sslService->userid;
        $vars['productName'] = $product->name;
        $vars['validTill'] = self::formatDate($sslService->getValidTill()->date);
        $vars['domains'] = $domains;
        $vars['domainCount'] = count($domainCount);
        $vars['wildcardDomainCount'] = count($wildcardDomainCount);
        $vars['domainLimits'] = $domainLimits;
        $vars['wildcardLimits'] = $wildcardLimits;
        $vars['configurationStatus'] = $sslService->status;

        $isOneTime = strtolower($product->paytype) === 'onetime';
        $validTill = new DateTime($sslService->getValidTill()->date);
        $daysUntilExpiry = (new DateTime())->diff($validTill)->days;
        $isExpiringSoon = $validTill > new DateTime() && $daysUntilExpiry <= 30;
        $vars['showRenewButton'] = $isOneTime && $isExpiringSoon;

        $vars['directoryUrl'] = $sslService->getDirectoryUrl();

        return [
            'tpl' => 'home_acme',
            'vars' => $vars
        ];

    }

    public function acmeConfiguration($input, $product, $vars): array
    {
        $serviceId = (int)$input['params']['serviceid'];
        $userId = (int)$input['params']['userid'];

        list($singleLimit, $wildcardLimit) = $this->getDomainLimits($input);

        $apiProduct = (new Products())->getProduct(KeyToIdMapping::getIdByKey($input['params'][C::API_PRODUCT_ID]));

        $client = $input['params']['clientsdetails'];

        $vars['serviceid'] = $serviceId;
        $vars['userid'] = $userId;
        $vars['productName'] = $product->name;
        $vars['singleDomainsLimit'] = $singleLimit;
        $vars['wildcardDomainsLimit'] = $wildcardLimit;
        $vars['isOrganizationRequired'] = $apiProduct->isOrganizationRequired();
        $vars['prefillOrganization'] = $client['companyname'] ?? '';
        $vars['prefillAddress'] = $client['address1'] ?? '';
        $vars['prefillCity'] = $client['city'] ?? '';
        $vars['prefillState'] = $client['fullstate'] ?? '';
        $vars['prefillPostalCode'] = $client['postcode'] ?? '';
        $vars['prefillCountry'] = $client['country'] ?? '';
        $vars['prefillFirstName'] = $client['firstname'] ?? '';
        $vars['prefillLastName'] = $client['lastname'] ?? '';
        $vars['prefillEmail'] = $client['email'] ?? '';

        return [
            'tpl' => 'acme_configuration',
            'vars' => $vars
        ];
    }

    public static function isAcmeProduct($product): bool
    {
        return str_contains($product->{C::API_PRODUCT_ID}, 'acme');
    }

    /**
     * @throws Exception
     */
    public function createSubscriptionJSON($input)
    {
        $domains = $input['domains'] ?? [];

        $paidNames = $this->validateDomainLimits($input, $domains);

        /**
         * @var AcmeApi $api
         */
        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);
        $customer = ApiProvider::getInstance()::getCustomer();
        $product = $input['params'][C::API_PRODUCT_ID];
        $period = $this->parsePeriod($input['params']['model']->billingcycle);
        $serviceId = $input['params']['serviceid'];
        $sslService = SSL::getByServiceId($serviceId);

        $approver = null;
        if (!empty($input['approverFirstName'])
            && !empty($input['approverLastName'])
            && !empty($input['approverEmail'])
            && !empty($input['approverVoice'])) {
            $approver = Approver::fromArray([
                'firstName' => $input['approverFirstName'],
                'lastName' => $input['approverLastName'],
                'jobTitle' => $input['approverJobTitle'] ?: null,
                'email' => $input['approverEmail'],
                'voice' => $input['approverVoice'],
            ]);
        }

        try {
            $response = $api->create(
            customer: $customer,
            product: $product,
            period: $period,
            domainNames: $domains,
            organization: $input['organization'] ?? null,
            country: $input['country'] ?? null,
            state: $input['state'] ?? null,
            address: $input['address'] ?? null,
            postalCode: $input['postalCode'] ?? null,
            city: $input['city'] ?? null,
            autoRenew: false, // Let WHMCS handle renewals
            approver: $approver
            );
        } catch (Exception $e) {
            $message = $e->getMessage();
            if (preg_match("/.*field '(.+)' is required .*/", $message, $matches)) {
                throw new Exception("'$matches[1]' is a required field");
            }
            throw $e;
        }


        $sslService->setRemoteId($response->id);
        $sslService->setAcmeCredentials($response->accountKey, $response->hmacKey);
        $sslService->setDirectoryUrl($response->directoryUrl);
        $this->updateAcmeConfigData($sslService, $domains, $paidNames);

        $sslService->save();

    }

    /**
     * @throws Exception
     */
    public function addDomainsJSON(array $input): void
    {
        $domains = $input['domains'] ?? [];

        $serviceId = $input['params']['serviceid'];
        $sslService = SSL::getByServiceId($serviceId);
        $remoteId = $sslService->getRemoteId();

        /** @var AcmeApi $api */
        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);

        $acmeSubscription = $api->get($remoteId);

        $paidNames = $this->validateDomainLimits($input, $domains, $sslService->getDomains());
        $newDomains = array_values(array_unique(array_merge($acmeSubscription->domainNames, $domains)));

        $api->update(
            acmeSubscriptionId: $remoteId,
            domainNames: $newDomains
        );

        $this->updateAcmeConfigData($sslService, $domains, $paidNames);
    }

    /**
     * @throws Exception
     */
    public function removeDomainJSON(array $input): void
    {
        $domain = $input['domain'] ?? null;

        if (empty($domain)) {
            throw new InvalidArgumentException('No domain provided');
        }

        $serviceId = $input['params']['serviceid'];
        $sslService = SSL::getByServiceId($serviceId);
        $remoteId = $sslService->getRemoteId();

        /** @var AcmeApi $api */
        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);
        $acmeSubscription = $api->get($remoteId);

        $newDomains = array_values(array_filter($acmeSubscription->domainNames, fn($d) => $d !== $domain));
        if (empty($newDomains)) {
            throw new InvalidArgumentException('Subscription needs at least one domain');
        }

        $api->update(
            acmeSubscriptionId: $remoteId,
            domainNames: $newDomains
        );

        $this->removeDomain($sslService, $domain);
        $this->updateAcmeConfigData($sslService);
    }

    /**
     * @throws Exception
     */
    public function showCredentialsJSON(array $input): array
    {
        $type = $input['type'] ?? null;
        $serviceId = $input['params']['serviceid'];
        $sslService = SSL::getByServiceId($serviceId);
        if ($type === 'accountKey') {
            return ['accountKey' => $sslService->getAccountKey()];
        } else if ($type === 'hmacKey') {
            return ['hmacKey' => $sslService->getHmacKey()];
        }

        return ['accountKey' => $sslService->getAccountKey(), 'hmacKey' => $sslService->getHmacKey()];
    }

    public function updateAcmeConfigData(SSL $sslService, $newNames = [], $paidNames = [])
    {

        $remoteId = $sslService->getRemoteId();
        /** @var AcmeApi $api */
        $api = ApiProvider::getInstance()->getApi(AcmeApi::class);
        $acmeSubscription = $api->get($remoteId);

        if ($acmeSubscription->status == AcmeSubscriptionStatusEnum::PENDING_ORGANIZATION_VALIDATION) {
            $sslService->setStatus(SSL::PENDING_ORGANIZATION_VALIDATION);
            $sslService->setSSLStatus(AcmeSubscriptionStatusEnum::PENDING_ORGANIZATION_VALIDATION);
        } else {
            $sslService->setStatus(SSL::ACTIVE);
            $sslService->setSSLStatus(AcmeSubscriptionStatusEnum::ACTIVE);
        }

        $sslService->setValidTill($acmeSubscription->expiryDate);
        $this->addDomains($sslService, $newNames, $paidNames);
        $sslService->save();
    }

    private function addDomains(SSL $sslService, array $newNames, array $paidNames)
    {
        $domains = $sslService->getDomains();
        foreach ($paidNames as $paidName) {
            if (!in_array($paidName, array_values(array_map(fn($domain) => $domain->domainName, $domains)))) {
                $domains[] = $this->getAcmeDomain($paidName, true);
            }
        }

        foreach ($newNames as $newName) {
            if (!in_array($newName, array_values(array_map(fn($domain) => $domain->domainName, $domains)))) {
                $domains[] = $this->getAcmeDomain($newName, false);
            }
        }
        $sslService->setDomains($domains);
    }

    private function getAcmeDomain(string $domainName, bool $isCharged)
    {
        return (object) [
            'domainName' => $domainName,
            'addedOn' => (new DateTime())->format('Y-m-d H:i:s'),
            'isCharged' => $isCharged
        ];
    }

    private function removeDomain(SSL $sslService, string $domain)
    {
        $domains = $sslService->getDomains();
        $domains = array_values(array_filter($domains, fn($domainObj) => $domainObj->domainName !== $domain));
        $sslService->setDomains($domains);
    }
}
