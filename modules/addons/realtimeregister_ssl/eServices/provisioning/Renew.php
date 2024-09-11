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
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use Exception;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;
use SandwaveIo\RealtimeRegister\Domain\DomainControlValidation;
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
        $apiConf = (new Repository())->get();
        if (
            isset($apiConf->automatic_processing_of_renewal_orders)
            && $apiConf->automatic_processing_of_renewal_orders == '1'
        ) {
            $resellerRenew = '';
            try {
                $this->checkRenew($this->p['serviceid']);
                $resellerRenew = $this->renewCertificate();
                if ($resellerRenew != 'beforeConfiguration') {
                    $this->updateOneTime();
                }
            } catch (Exception $ex) {
                return $ex->getMessage();
            }
            if ($resellerRenew != 'beforeConfiguration') {
                $this->addRenew($this->p['serviceid']);
            }
            return 'success';
        } else {
            Capsule::table('tblsslorders')->where('serviceid', $this->p['serviceid'])->update([
                'remoteid' => '',
                'configdata' => '',
                'completiondate' => '0000-00-00 00:00:00',
                'status' => 'Awaiting Configuration'
            ]);
            return 'success';
        }
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
        $checkTable = Capsule::schema()->hasTable('mod_REALTIMEREGISTERSSL_renew');
        if ($checkTable === false) {
            Capsule::schema()->create('mod_REALTIMEREGISTERSSL_renew', function ($table) {
                $table->increments('id');
                $table->integer('serviceid');
                $table->dateTime('date');
            });
        }
    }

    private function checkRenew($serviceid)
    {
        $this->createRenewTable();

        $renew = Capsule::table('mod_REALTIMEREGISTERSSL_renew')->where('serviceid', $serviceid)->where(
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

        $renew = Capsule::table('mod_REALTIMEREGISTERSSL_renew')->where('serviceid', $serviceid)->first();

        if (isset($renew->id) && !empty($renew->id)) {
            Capsule::table('mod_REALTIMEREGISTERSSL_renew')->where('serviceid', $serviceid)->update([
                'date' => date('Y-m-d H:i:s')
            ]);
        } else {
            Capsule::table('mod_REALTIMEREGISTERSSL_renew')->insert([
                'serviceid' => $serviceid,
                'date' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private function renewCertificate()
    {
        echo __METHOD__;
        $this->loadSslService();
        $this->loadApiProduct();

        if (!isset($this->sslService->configdata) || empty($this->sslService->configdata)) {
            return 'beforeConfiguration';
        }

        /** @var CertificatesApi $certificateApi */
        $certificateApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
        $service = Capsule::table('tblhosting')->where('id', $this->p['serviceid'])->first();
        $sslData = Capsule::table('tblsslorders')->where('serviceid', $this->p['serviceid'])->first();
        $configData = json_decode($sslData->configdata, true);

        $order = Capsule::table('REALTIMEREGISTERSSL_orders')->where('service_id', $this->p['serviceid'])->first();
        $orderDetails = json_decode($order->data, true);

        $dcv = [];
        foreach ($orderDetails['validations']['dcv'] as $validation) {
            $dcvTmp = [
                'commonName' => $validation['commonName'],
                'type' => $validation['type'],
            ];
            if ($validation['type'] === 'EMAIL') {
                $dcvTmp['email'] = $validation['email'];
            }
            $dcv[] = $dcvTmp;
        }
        $addSSLRenewOrder = $certificateApi->renewCertificate(
            $configData['certificateId'],
            $this->p['configoption2'],
            $configData['csr'],
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $dcv
        );
        /** @var ProcessesApi $processesApi */
        $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
        $orderDetails = $certificateApi->listCertificates(1, null,null, ['process:eq' => $addSSLRenewOrder->remoteId]);

        $this->sslService->setRemoteId($orderDetails->id);
        $this->sslService->setOrderStatusDescription($orderDetails->status);
        $this->sslService->setSSLStatus($orderDetails->status);
        $this->sslService->save();

        $logs = new LogsRepo();
        /** @var DomainControlValidation $data */
        foreach ($addSSLRenewOrder->validations->dcv as $data) {
            try {
                $panel = Panel::getPanelData($data->commonName);

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
                        $revalidate = true;
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

//            if (isset($addSSLRenewOrder['san']) && !empty($addSSLRenewOrder['san'])) {
//                foreach ($addSSLRenewOrder['san'] as $sanrecord) {
//                    $records = [];
//                    if (
//                        isset($sanrecord['validation']['dns']['record'])
//                        && !empty($sanrecord['validation']['dns']['record'])
//                    ) {
//                        if (file_exists($loaderDNS)) {
//                            $helper = new DomainHelper(str_replace('*.', '', $sanrecord['san_name']));
//                            $zoneDomain = $helper->getDomainWithTLD();
//                        }
//
//                        if (strpos($sanrecord['validation']['dns']['record'], 'CNAME') !== false) {
//                            $dnsrecord = explode("CNAME", $sanrecord['validation']['dns']['record']);
//                            $records[] = [
//                                'name' => trim(rtrim($dnsrecord[0])) . '.',
//                                'type' => 'CNAME',
//                                'ttl' => '3600',
//                                'data' => trim(rtrim($dnsrecord[1]))
//                            ];
//                        } else {
//                            $dnsrecord = explode("IN   TXT", $sanrecord['validation']['dns']['record']);
//                            $length = strlen(trim(rtrim($dnsrecord[1])));
//                            $records[] = [
//                                'name' => trim(rtrim($dnsrecord[0])) . '.',
//                                'type' => 'TXT',
//                                'ttl' => '14440',
//                                'data' => substr(trim(rtrim($dnsrecord[1])), 1, $length - 2)
//                            ];
//                        }
//
//                        $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
//                        if (!isset($zone->id) || empty($zone->id)) {
//                            $postfields = [
//                                'action' => 'dnsmanager',
//                                'dnsaction' => 'createZone',
//                                'zone_name' => $zoneDomain,
//                                'type' => '2',
//                                'relid' => $this->p['serviceid'],
//                                'zone_ip' => '',
//                                'userid' => $this->p['userid']
//                            ];
//                            $createZoneResults = localAPI('dnsmanager', $postfields);
//                            logModuleCall(
//                                'RealtimeRegisterSsl [dns]',
//                                'createZone',
//                                print_r($postfields, true),
//                                print_r($createZoneResults, true)
//                            );
//                        }
//
//                        $zone = Capsule::table('dns_manager2_zone')->where('name', $zoneDomain)->first();
//                        if (isset($zone->id) && !empty($zone->id)) {
//                            $postfields = [
//                                'dnsaction' => 'createRecords',
//                                'zone_id' => $zone->id,
//                                'records' => $records
//                            ];
//                            $createRecordCnameResults = localAPI('dnsmanager', $postfields);
//                            logModuleCall(
//                                'RealtimeRegisterSsl [dns]',
//                                'updateZone',
//                                print_r($postfields, true),
//                                print_r($createRecordCnameResults, true)
//                            );
//                        }
//                    }
//                }
//            }
//        }

        Capsule::table('tblsslorders')->where('serviceid', $this->p['serviceid'])->update([
            'remoteid' => $addSSLRenewOrder->certificateId
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
