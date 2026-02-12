<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use Exception;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Exceptions\BadRequestException;
use WHMCS\Database\Capsule;

class Renew
{
    use SSLUtils;

    private $p;

    /**
     *
     * @var \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    private $sslService;

    /**
     * @var Product
     */
    private $apiProduct;

    public function __construct(&$params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        $logs = new LogsRepo();
        try {
            $this->renewCertificate();
        } catch (Exception $ex) {
            $logs->addLog(
                $this->p['userid'],
                $this->p['serviceid'],
                'error',
                '[' . $this->p['serviceid'] . '] Error:' . $ex->getMessage()
            );
            return $ex->getMessage();
        }
        return "success";
    }

    private function updateOneTime()
    {
        $serviceID = $this->p['serviceid'];

        $service = (array)Capsule::table('tblhosting')->where('id', $serviceID)->first();
        $product = (array)Capsule::table('tblproducts')
            ->where('servertype', 'realtimeregister_ssl')->where('id', $service['packageid'])->first();

        $sslOrder = (array)Capsule::table('tblsslorders')->where('serviceid', $serviceID)->first();
        $configdata = json_decode($sslOrder['configdata'], true);

        if (
            isset($product['configoption7']) && !empty($product['configoption7'])
            && $service['billingcycle'] == 'One Time'
        ) {
            Capsule::table('tblhosting')->where('id', $serviceID)->update(
                ['termination_date' => $configdata['valid_till']]
            );
        }
    }

    private function createRenewTable()
    {
        $checkTable = Capsule::schema()->hasTable('REALTIMEREGISTERSSL_renew');
        if ($checkTable === false) {
            Capsule::schema()->create('REALTIMEREGISTERSSL_renew', function ($table) {
                $table->increments('id');
                $table->integer('serviceid');
                $table->dateTime('date');
            });
        }
    }

    private function checkRenew($serviceid)
    {
        $this->createRenewTable();

        $renew = Capsule::table('REALTIMEREGISTERSSL_renew')->where('serviceid', $serviceid)->where(
            'date',
            'like',
            date('Y-m-d H') . '%'
        )->first();

        if (isset($renew->id) && !empty($renew->id)) {
            throw new Exception('Block double renew.');
        }
    }

    private function addRenew($serviceid)
    {
        $this->createRenewTable();

        $renew = Capsule::table('REALTIMEREGISTERSSL_renew')->where('serviceid', $serviceid)->first();

        if (isset($renew->id) && !empty($renew->id)) {
            Capsule::table('REALTIMEREGISTERSSL_renew')->where('serviceid', $serviceid)->update([
                'date' => date('Y-m-d H:i:s')
            ]);
        } else {
            Capsule::table('REALTIMEREGISTERSSL_renew')->insert([
                'serviceid' => $serviceid,
                'date' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private function renewCertificate() : void
    {
        $this->loadSslService();
        $this->loadApiProduct();

        $logs = new LogsRepo();

        $service = Capsule::table('tblhosting')->where('id', $this->p['serviceid'])->first();
        $sslData = Capsule::table('tblsslorders')->where('serviceid', $this->p['serviceid'])->first();
        $configData = json_decode($sslData->configdata, true);
        $order = Capsule::table('REALTIMEREGISTERSSL_orders')->where('service_id', $this->p['serviceid'])->first();
        $orderDetails = json_decode($order->data, true);
        $commonName = $orderDetails['commonName'] ?? $orderDetails['domain'];

        $dcv = [];
        foreach ($orderDetails['validations']['dcv'] as $validation) {
            $dcvEntry = [
                'commonName' => $validation['commonName'],
                'type' => $validation['type'],
            ];
            if ($validation['type'] === 'EMAIL') {
                $dcvEntry['email'] = $validation['email'];
            }
            $dcv[] = $dcvEntry;
        }
        if (empty($dcv) && $orderDetails['dcv_method']) {
            $dcv[] = [
                'commonName' => $commonName,
                'type' => $orderDetails['dcv_method'] == 'HTTP' ? 'FILE' : $orderDetails['dcv_method']
            ];
        }

        /** @var \RealtimeRegister\Domain\Product $productDetails */
        $productDetails = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->getProduct($orderDetails['product_id']);

        $orderFields = $this->mapRequestFields($orderDetails, $productDetails);
        $orderFields['san'] = empty($configData['san_details'])
            ? null
            : array_map(fn($san) => $san['san_name'], $configData['san_details']);
        $orderFields['period'] = $this->parsePeriod($service->billingcycle);
        $orderFields['product'] = $orderDetails['product_id'];
        $orderFields['csr'] = $configData['csr'];
        $orderFields['dcv'] = $dcv;

        $authKey = $this->p[ConfigOptions::AUTH_KEY_ENABLED];
        if ($authKey) {
            $authKey = $this->processAuthKeyValidation($commonName, $orderFields['product'], $orderFields['csr'], $dcv);
        }

        $addSSLRenewOrder = $this->tryOrder($configData['certificateId'], $orderFields, $commonName, $authKey);

        $this->sslService->setRemoteId($addSSLRenewOrder->processId);
        $this->sslService->setOrderStatusDescription("Pending");
        $this->sslService->setSSLStatus("SUSPENDED");
        $this->sslService->save();

        $this->processDcvEntries($addSSLRenewOrder->validations?->dcv?->toArray() ?? []);

        Capsule::table('tblsslorders')
            ->where('serviceid', $this->p['serviceid'])
            ->update(['remoteid' => $addSSLRenewOrder->processId]);
        $this->loadSslService();

        try {
            $configDataUpdate = new UpdateConfigData($this->sslService);
            $configDataUpdate->run();
        } catch (\Exception $e) {
            $logs->addLog(
                $this->p['userid'],
                $this->p['serviceid'],
                'error',
                '[' . $commonName . '] Error:' . $e->getMessage()
            );
        }

        $logs->addLog($this->p['userid'],
            $this->p['serviceid'],
            'success',
            'The renew order has been placed ' . ($addSSLRenewOrder->certificateId ? ' the certificate was issued immediately.' : '.')
        );

        if ($addSSLRenewOrder->certificateId) {
            $this->installCertificate($this->sslService);
        }
    }

    private function loadSslService()
    {
        $ssl = new SSL();
        $this->sslService = $ssl->getByServiceId($this->p['serviceid']);

        if (is_null($this->sslService)) {
            throw new Exception('Create has not been initialized');
        }
    }

    private function loadApiProduct()
    {
        $apiProductId = $this->p[ConfigOptions::API_PRODUCT_ID];
        $apiRepo = new Products();
        $this->apiProduct = $apiRepo->getProduct(KeyToIdMapping::getIdByKey($apiProductId));
    }

    private function tryOrder(int $certificateId, array $orderData, string $commonName, bool $authKey) {
        $sslOrder = [
            'period' => $orderData['period'],
            'csr' => $orderData['csr'],
            'san' => empty($orderData['san']) ? null : $orderData['san'],
            'organization' => $orderData['organization'],
            'department' => $orderData['department'],
            'address' => $orderData['address'],
            'postalCode' => $orderData['postalCode'],
            'city' => $orderData['city'],
            'coc' => $orderData['coc'],
            'approver' => $orderData['approver'],
            'country' => null,
            'language' => null,
            'dcv' => $orderData['dcv'],
            'domainName' => $commonName,
            'authKey' => $authKey,
            'state' => $orderData['state'],
        ];
        return $this->sendRequest($certificateId, $sslOrder);
    }

    private function sendRequest(int $certificateId, array $sslOrder)
    {
        $logs = new LogsRepo();
        try {
            return ApiProvider::getInstance()
                ->getApi(CertificatesApi::class)
                ->renewCertificate($certificateId, ...$sslOrder);
        } catch (BadRequestException $exception) {
            $logs->addLog(
                $this->p['userid'],
                $this->p['serviceid'],
                'error',
                '[' . $sslOrder['domainName'] . '] Error:' . $exception->getMessage()
            );
            $decodedMessage = json_decode(str_replace('Bad Request: ', '', $exception->getMessage()), true);
            $retry = false;
            switch ($decodedMessage['type']) {
                case 'ConstraintViolationException':
                case 'ObjectExists':
                    break;
                default:
                    $retry = true;
                    break;
            }

            if ($retry && $sslOrder['authKey']) {
                return $this->sendRequest($certificateId, [...$sslOrder, 'authKey' => false]);
            }
            throw $exception;
        }
    }
}
