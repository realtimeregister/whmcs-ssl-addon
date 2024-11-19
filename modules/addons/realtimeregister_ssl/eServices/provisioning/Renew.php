<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl\Product;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Dns\DnsControl;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\File\FileControl;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use Exception;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Domain\DomainControlValidation;
use WHMCS\Database\Capsule;

class Renew
{
    private $p;

    /**
     *
     * @var \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL
     */
    private $sslService;

    /**
     *
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

        /** @var CertificatesApi $certificateApi */
        $certificateApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
        $service = Capsule::table('tblhosting')->where('id', $this->p['serviceid'])->first();
        $sslData = Capsule::table('tblsslorders')->where('serviceid', $this->p['serviceid'])->first();
        $configData = json_decode($sslData->configdata, true);
        $order = Capsule::table('REALTIMEREGISTERSSL_orders')->where('service_id', $this->p['serviceid'])->first();
        $orderDetails = json_decode($order->data, true);

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

        /** @var \RealtimeRegister\Domain\Product $productDetails */
        $productDetails = ApiProvider::getInstance()
            ->getApi(CertificatesApi::class)
            ->getProduct($orderDetails['product_id']);

        $mapping = [
            'organization' => 'orgname',
            'country' => 'country',
            'state' => 'state',
            'address' => 'address1',
            'postalCode' => 'postcode',
            'city' => 'city',
            'dcv' => 'dcv'
        ]; // 'coc','language', 'uniqueValue','authKey' == missing

        $orderFields = [];
        foreach ($productDetails->requiredFields as $value) {
            if ($value === 'approver') {
                $orderFields['approver'] = [
                    'firstName' => $this->p['firstname'],
                    'lastName' => $this->p['lastname'],
                    'jobTitle' => $this->p['jobtitle'],
                    'email' => $this->p['email'],
                    'voice' => $this->p['phonenumber']
                ];
            } else {
                $orderFields[$value] = $orderDetails[$mapping[$value]];
            }
        }

        $addSSLRenewOrder = $certificateApi->renewCertificate(
            $configData['certificateId'],
            intval($this->p['configoptions']['years']) * 12,
            $configData['csr'],
            array_map(fn($san) => $san['san_name'], $configData['san_details']),
            $orderFields['organization'],
            null,
            $orderFields['address'],
            $orderFields['postalCode'],
            $orderFields['city'],
            null,
            $orderFields['approver']['email'],
            $orderFields['approver'],
            null,
            null,
            $dcv,
            $orderDetails['domain'],
            null,
            $orderFields['state'],
            $orderDetails['product_id']
        );

        $this->sslService->setRemoteId($addSSLRenewOrder->processId);
        $this->sslService->setOrderStatusDescription("Pending");
        $this->sslService->setSSLStatus("SUSPENDED");
        $this->sslService->save();

        $logs = new LogsRepo();
        /** @var DomainControlValidation $data */
        foreach ($addSSLRenewOrder->validations->dcv as $data) {
            try {
                $panel = Panel::getPanelData($data->commonName);
                if (!$panel) {
                    continue;
                }

                if ($data->type == 'FILE') {
                    $result = FileControl::create(
                        [
                            'fileLocation' => $data->fileLocation, // whole url,
                            'fileContents' => $data->fileContents
                        ],
                        $panel
                    );

                    if ($result['status'] === 'success') {
                        $logs->addLog(
                            $this->p['userid'],
                            $this->p['serviceid'],
                            'success',
                            'The ' . $service->domain . ' domain has been verified using the file method.'
                        );
                    }
                } elseif ($data->type == 'DNS') {
                    if ($data->dnsType == 'CNAME') {

                        $result = DnsControl::generateRecord($data->toArray(), $panel);
                        if ($result) {
                            $logs->addLog(
                                $this->p['userid'],
                                $this->p['serviceid'],
                                'success',
                                'The ' . $service->domain . ' domain has been verified using the dns method.'
                            );
                        }
                    }
                }
            } catch (Exception $e) {
                $logs->addLog(
                    $this->p['userid'],
                    $this->p['serviceid'],
                    'error',
                    '[' . $service->domain . '] Error:' . $e->getMessage()
                );
                continue;
            }
        }

        Capsule::table('tblsslorders')->where('serviceid', $this->p['serviceid'])->update([
            'remoteid' => $addSSLRenewOrder->processId ?? $addSSLRenewOrder->certificateId
        ]);
        $this->loadSslService();

        $configDataUpdate = new UpdateConfigData($this->sslService);
        $configDataUpdate->run();
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
}
