<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\server\clientarea;

use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractController;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Config;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Panel;
use AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Deploy\Manage;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ClientRecheckCertificateDetails;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions as C;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\UpdateConfigData;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use AddonModule\RealtimeRegisterSsl\models\logs\Repository as LogsRepo;
use AddonModule\RealtimeRegisterSsl\models\orders\Repository as OrderRepo;
use AddonModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use AddonModule\RealtimeRegisterSsl\Server;
use DateTimeImmutable;
use Exception;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Api\ProcessesApi;
use RealtimeRegister\Domain\ResendDcvCollection;
use WHMCS\Database\Capsule;

/**
 * Description of home
 *
 */
class home extends AbstractController
{
    public function indexHTML($input, $vars = [])
    {
        try {
            global $CONFIG;

            if ($input['params']['status'] != 'Active') {
                return true;
            }

            $disabledValidationMethods = [];

            $serviceId = $input['params']['serviceid'];
            $serviceBillingCycle = $input['params']['templatevars']['billingcycle'];
            $userid = $input['params']['userid'];
            $ssl = new SSLRepo();
            $sslService = $ssl->getByServiceId($serviceId);

            $sslStatus = $sslService->configdata->ssl_status;

            if (
                (
                    !$sslStatus
                    || $sslStatus == 'pending'
                    || $sslStatus == 'reissue'
                    || $sslStatus == 'new_order'
                    || $sslStatus == 'processing'
                    || $sslStatus == 'SUSPENDED'
                    || $sslStatus == ''
                )
                && $sslService->remoteid != ''
            ) {
                $sslRepo = new SSLRepo();
                $sslService = $sslRepo->getByServiceId($serviceId);

                if (is_null($sslService)) {
                    throw new Exception('Create has not been initialized');
                }

                if ($input['params']['userid'] != $sslService->userid) {
                    throw new Exception('An error occurred');
                }

                /** @var ProcessesApi $processesApi */
                $processesApi = ApiProvider::getInstance()->getApi(ProcessesApi::class);
                $infoProcess = [];
                $apicertdata = $processesApi->get($sslService->getRemoteId())->toArray();
                if ($apicertdata['status'] != 'COMPLETED') {
                    $infoProcess = $processesApi->info($sslService->getRemoteId())->toArray();
                }

                $configDataUpdate = new UpdateConfigData($sslService, [
                    'status' => $apicertdata['status'],
                    'dcv' => $infoProcess['validations']['dcv']
                ]);

                $configDataUpdate->run();

                $vars['requiresAttention'] = $infoProcess['requiresAttention'];
                $vars['activationStatus'] = $sslService->getSSLStatus();
            }

            if (is_null($sslService)) {
                throw new Exception('An error occurred please contact support');
            }

            $url = Config::getInstance()->getConfigureSSLUrl($sslService->id, $serviceId);

            $vars['privateKey'] = '';
            $privateKey = $sslService->getPrivateKey();
            if ($privateKey) {
                $vars['privateKey'] = $privateKey;
            }
            $vars['san_revalidate'] = false;

            if ($sslService->status !== SSL::AWAITING_CONFIGURATION) {
                try {
                    $certificateDetails = (array)$sslService->configdata;

                    if (!empty($certificateDetails['crt'])) {
                        $sslOrderRepo = new OrderRepo();
                        $checkOrderSSL = $sslOrderRepo->checkOrdersInstallation($serviceId);

                        $panelData = Panel::getPanelData($certificateDetails['commonName']);
                        if ($panelData !== false && $checkOrderSSL === true) {
                            $vars['btnInstallCrt'] = true;
                        }
                    }

                    if (!empty($certificateDetails['partner_order_id'])) {
                        $vars['partner_order_id'] = $certificateDetails['partner_order_id'];
                    }
                    if (!empty($certificateDetails['product_brand'])) {
                        $vars['brand'] = $certificateDetails['product_brand'];
                    }

                    if (!empty($certificateDetails['dcv_method'])) {
                        $vars['dcv_method'] = $certificateDetails['dcv_method'];

                        if (in_array($vars['dcv_method'], ["http", "dns"])) {
                            if (is_array($certificateDetails['approver_method'])) {
                                $vars['approver_method'][$vars['dcv_method']] = $certificateDetails['approver_method'][$vars['dcv_method']];
                            } else {
                                $vars['approver_method'][$vars['dcv_method']] = (array)$certificateDetails['approver_method']->{$vars['dcv_method']};
                            }

                            if ($vars['dcv_method'] == 'http') {
                                $vars['approver_method'][$vars['dcv_method']]['content'] = explode(
                                    PHP_EOL,
                                    $vars['approver_method'][$vars['dcv_method']]['content']
                                );
                            }
                        } else {
                            $vars['dcv_method'] = 'email';
                            $vars['approver_method'] = $certificateDetails['fields']->approveremail;
                        }
                    }

                    if (!empty($certificateDetails['csr'])) {
                        $vars['csr'] = ($certificateDetails['csr']);
                    }

                    $now = new \DateTime();

                    if (!empty($certificateDetails['crt'])) {
                        $vars['crt'] = ($certificateDetails['crt']);
                    }


                    if (!empty($certificateDetails['ca'])) {
                        $vars['ca'] = ($certificateDetails['ca']);
                    }

                    if (!empty($certificateDetails['domain'])) {
                        $vars['domain'] = $certificateDetails['domain'];
                    }

                    if (!empty($certificateDetails['san_details'])) {
                        foreach ($certificateDetails['san_details'] as $san) {
                            $vars['sans'][$san->san_name]['san_name'] = $san->san_name;
                            $vars['sans'][$san->san_name]['method'] = $san->validation_method;
                            switch ($san->validation_method) {
                                case 'dns':
                                    $vars['san_revalidate'] = true;
                                    $vars['sans'][$san->san_name]['san_validation'] = $san->validation->dns->record;
                                    break;
                                case 'http':
                                    $vars['san_revalidate'] = true;
                                    $vars['sans'][$san->san_name]['san_validation'] = (array)$san->validation->http;
                                    $vars['sans'][$san->san_name]['san_validation']['content'] =
                                        explode(PHP_EOL, $san->validation->http->content);
                                    break;
                                default:
                                    $vars['sans'][$san->san_name]['san_validation'] = $san->validation->email;
                                    break;
                            }
                        }
                    }
                    if (!$vars['activationStatus']) {
                        $vars['activationStatus'] = $certificateDetails['ssl_status'];
                    }

                    $daysUntilExpired = null;

                    if ($certificateDetails['valid_from'] && $certificateDetails['valid_till']) {
                        $vars['validFrom'] = self::formatDate($certificateDetails['valid_from']->date);
                        $vars['validTill'] = self::formatDate($certificateDetails['valid_till']->date);
                        $daysUntilExpired = $now->diff(new \DateTime(($certificateDetails['valid_till'])->date))
                            ->format('%a');
                    }

                    $vars['nextReissue'] = $daysUntilExpired;

                    $vars['displayRenewButton'] = false;

                    if (!empty($certificateDetails['end_date'])) {
                        $vars['subscriptionEnds'] = self::formatDate($certificateDetails['end_date']->date);
                        $daysUntilExpired = $now->diff(new \DateTime($certificateDetails['end_date']->date))
                            ->format('%a');
                    }

                    //dump($daysUntilExpired, $serviceBillingCycle);
                    if ($daysUntilExpired && (int)$daysUntilExpired < 30 && $serviceBillingCycle == 'One Time') {
                        $vars['displayRenewButton'] = true;
                    }

                    if (!empty($certificateDetails['begin_date'])) {
                        $vars['subscriptionStarts'] = self::formatDate($certificateDetails['begin_date']->date);
                    }


                    //service billing cycle
                    $vars['serviceBillingCycle'] = $serviceBillingCycle;

                    $disabledValidationMethods = [];

                    $product = new Product($input['params']['pid']);
                    $productssl = false;
                    $checkTable = Capsule::schema()->hasTable(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND);
                    if ($checkTable) {
                        if (Capsule::schema()->hasColumn(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND, 'data')) {
                            $productsslDB = Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)
                                ->where('pid', KeyToIdMapping::getIdByKey($product->configuration()->text_name))
                                ->first();
                            if (isset($productsslDB->data)) {
                                $productssl['product'] = json_decode($productsslDB->data, true);
                            }
                        }
                    }
                } catch (Exception $ex) {
                    $vars['error'] = 'Can not load order details';
                }
            }

            $apiConf = (new Repository())->get();

            $vars['custom_guide'] = $apiConf->custom_guide;
            $vars['visible_renew_button'] = $apiConf->visible_renew_button;
            $vars['disabledValidationMethods'] = $disabledValidationMethods;
            $vars['configurationStatus'] = $sslService->status;
            $vars['configurationURL'] = $url;
            $vars['allOk'] = true;
            $vars['assetsURL'] = Server::I()->getAssetsURL();
            $vars['serviceid'] = $serviceId;
            $vars['userid'] = $userid;

            $filenameCsr = !empty($vars['domain']) ? $vars['domain'] : 'csr_code';
            $filenameCrt = !empty($vars['domain']) ? $vars['domain'] : 'crt_code';
            $filenameCa = !empty($vars['domain']) ? $vars['domain'] : 'ca_code';

            if ($_GET['download'] == '1') {
                if (
                    !empty($vars['sans'][$_GET['domain']])
                    && ($vars['sans'][$_GET['domain']]['method'] == 'http')
                ) {
                    header('Content-Type: application/octet-stream');
                    header(
                        'Content-Disposition: attachment; filename=' . basename(
                            $vars['sans'][$_GET['domain']]['san_validation']['filename']
                        )
                    );
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    print implode(PHP_EOL, $vars['sans'][$_GET['domain']]['san_validation']['content']);
                    exit;
                }

                if (!empty($vars['approver_method']['http'])) {
                    header('Content-Type: application/octet-stream');
                    header(
                        'Content-Disposition: attachment; filename='
                        . basename($vars['approver_method']['http']['link'])
                    );
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    print implode(PHP_EOL, $vars['approver_method']['http']['content']);
                    exit;
                }
            }

            if ($_GET['downloadcsr'] == '1' && !empty($certificateDetails['csr'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . $filenameCsr . '.csr');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                print $certificateDetails['csr'];
                exit;
            }
            if ($_GET['downloadcrt'] == '1' && !empty($certificateDetails['crt'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . $filenameCrt . '.crt');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                print $certificateDetails['crt'];
                exit;
            }
            if ($_GET['downloadca'] == '1' && !empty($certificateDetails['ca'])) {
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . $filenameCa . '.ca');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                print $certificateDetails['ca'];
                exit;
            }
            if (
                $_GET['downloadpem'] == '1' && !empty($certificateDetails['crt']) && !empty($certificateDetails['ca'])
            ) {
                $pemfile = '';

                $sslRepo = new SSLRepo();
                $sslService = $sslRepo->getByServiceId($input['params']['serviceid']);
                $privateKey = $sslService->getPrivateKey();

                if (!empty($privateKey)) {
                    if (str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
                        $pemfile .= $privateKey;
                    } else {
                        $pemfile .= decrypt($privateKey);
                    }
                }

                $pemfile .= $certificateDetails['crt'] . "\n";
                $pemfile .= $certificateDetails['ca'];

                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . $filenameCa . '.pem');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                print $pemfile;
                exit;
            }

            $vars['actual_link'] = $CONFIG['SystemURL'] . '/clientarea.php?action=productdetails&id=' . $vars['serviceid'];

            $vars['btndownload'] = false;

            if (!empty($certificateDetails['csr'])) {
                $vars['downloadcsr'] = $vars['actual_link'] . '&downloadcsr=1';
            }

            if (!empty($certificateDetails['crt'])) {
                $vars['downloadcrt'] = $vars['actual_link'] . '&downloadcrt=1';
            }

            if (!empty($certificateDetails['ca'])) {
                $vars['downloadca'] = $vars['actual_link'] . '&downloadca=1';
            }

            if (!empty($certificateDetails['crt']) && !empty($certificateDetails['ca'])) {
                $vars['downloadpem'] = $vars['actual_link'] . '&downloadpem=1';
            }

            if (!empty($vars['approver_method']['http'])) {
                $vars['btndownload'] = $vars['actual_link'] . '&download=1';
            }

            foreach ($vars['sans'] as $detailssan) {
                if ($detailssan['method'] == 'http') {
                    $vars['btndownload'] = $vars['actual_link'] . '&download=1&domain=' . $detailssan['san_name'];
                }
            }
        } catch (Exception $ex) {
            $vars['error'] = $ex->getMessage();
        }

        $vars[C::OPTION_ISSUED_SSL_MESSAGE] = $input['params'][C::OPTION_ISSUED_SSL_MESSAGE];
        $vars[C::OPTION_CUSTOM_GUIDE] = $input['params'][C::OPTION_CUSTOM_GUIDE];

        $vars['approver_email'] = isset($sslService->configdata->approver_method->email)
        && !empty($sslService->configdata->approver_method->email) ? $sslService->configdata->approver_method->email
            : false;

        return [
            'tpl' => 'home',
            'vars' => $vars
        ];
    }

    public function renewJSON($input, $vars = [])
    {
        try {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: The renewal action was initiated for the Service ID: " . $input['id']
            );

            $errorInvoiceExist = false;
            // TODO fix the following lines
            $service = \WHMCS\Service\Service::where('id', $input['id'])->first();
            $result = $this->createAutoInvoice($input['params']['pid'], $service, true);
            if (is_array($result) && isset($result['invoiceID'])) {
                $existInvoiceID = $result['invoiceID'];
                $errorInvoiceExist = Lang::getInstance()->T('Related invoice already exist.');
            }
        } catch (Exception $e) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMC Renew Action Error: " . $e->getMessage()
            );
            return [
                'error' => $e->getMessage(),
            ];
        }
        if ($errorInvoiceExist) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMC Renew Action Error: " . $errorInvoiceExist
            );

            return [
                'error' => $errorInvoiceExist,
                'invoiceID' => $existInvoiceID
            ];
        }

        Whmcs::savelogActivityRealtimeRegisterSsl(
            "Realtime Register SSL WHMC Renew Action: A new invoice has been successfully created for the Service ID: "
            . $input['id']
        );
        return [
            'success' => true,
            'msg' => Lang::getInstance()->T('A new invoice has been successfully created. '),
            'invoiceID' => $result
        ];
    }

    public function resendValidationEmailJSON($input, $vars = [])
    {
        $ssl = new SSLRepo();
        $serviceSSL = $ssl->getByServiceId($input['id']);
        $resendDcv = [];
        foreach ($serviceSSL->configdata->validations->dcv as $dcv) {
            if ($dcv->type == "EMAIL") {
                $resendDcv[] = [
                    "commonName" => $dcv->commonName,
                    "type" => "EMAIL",
                    "email" => $dcv->email,
                ];
            }
        }
        ApiProvider::getInstance()->getApi(CertificatesApi::class)
            ->resendDcv($serviceSSL->getRemoteId(), ResendDcvCollection::fromArray($resendDcv));

        return [
            'success' => "Successfully resent DCV"
        ];
    }

    public function sendCertificateEmailJSON($input, $vars = [])
    {
        $ssl = new SSLRepo();
        $orderStatus = $ssl->getByServiceId($input['id']);

        if ($orderStatus->getSSLStatus() !== 'COMPLETED' && $orderStatus->getSSLStatus() !== 'ACTIVE') {
            throw new Exception(
                Lang::getInstance()->T('orderNotActiveError')
            ); //Can not send certificate. Order status is different than active.
        }

        $apiConf = (new Repository())->get();
        $sendCertificateTemplate = $apiConf->send_certificate_template;

        $pathAttachments = false;
        $checkSettings = Capsule::schema()->hasTable('tblfileassetsettings');
        if ($checkSettings !== false) {
            $filesetting = Capsule::table('tblfileassetsettings')->where('asset_type', 'email_attachments')->first();
            if (isset($filesetting->storageconfiguration_id) && !empty($filesetting->storageconfiguration_id)) {
                $checkStorage = Capsule::schema()->hasTable('tblstorageconfigurations');
                if ($checkStorage !== false) {
                    $storage = Capsule::table('tblstorageconfigurations')
                        ->where('id', $filesetting->storageconfiguration_id)->first();
                    if (isset($storage->settings) && !empty($storage->settings)) {
                        $storageData = json_decode($storage->settings, true);
                        if (isset($storageData['local_path']) && !empty($storageData['local_path'])) {
                            $pathAttachments = $storageData['local_path'];
                        }
                    }
                }
            }
        }

        $attachments = [];
        if (!empty($orderStatus->getCa())) {
            if ($pathAttachments === false) {
                $tmp_ca_code = tempnam("/tmp", "FOO");
                $handle = fopen($tmp_ca_code, "w");
                fwrite($handle, $orderStatus['ca_code']);
                fclose($handle);

                $attachments[] = [
                    'displayname' => 'ca_code.ca',
                    'path' => $tmp_ca_code
                ];
            } else {
                $filetemp = $pathAttachments . DIRECTORY_SEPARATOR . $input['params']['serviceid']
                    . $input['params']['accountid'] . '_ca_code.ca';
                file_exists($filetemp) || touch($filetemp);
                file_put_contents($filetemp, $orderStatus->getCa());

                $attachments[] = [
                    'displayname' => $input['params']['serviceid'] . $input['params']['accountid'] . '_ca_code.ca',
                    'filename' => $input['params']['serviceid'] . $input['params']['accountid'] . '_ca_code.ca'
                ];
            }
        }

        if (!empty($orderStatus->getCrt())) {
            if ($pathAttachments === false) {
                $tmp_crt_code = tempnam("/tmp", "FOO");
                $handle = fopen($tmp_crt_code, "w");
                fwrite($handle, $orderStatus->getCrt());
                fclose($handle);

                $attachments[] = [
                    'displayname' => 'crt_code.crt',
                    'path' => $tmp_crt_code
                ];
            } else {
                $filetemp = $pathAttachments . DIRECTORY_SEPARATOR . $input['params']['serviceid']
                    . $input['params']['accountid'] . '_crt_code.crt';
                file_exists($filetemp) || touch($filetemp);
                file_put_contents($filetemp, $orderStatus->getCrt());

                $attachments[] = [
                    'displayname' => $input['params']['serviceid'] . $input['params']['accountid'] . '_crt_code.crt',
                    'filename' => $input['params']['serviceid'] . $input['params']['accountid'] . '_crt_code.crt'
                ];
            }
        }

        if (!empty($orderStatus->getCsr())) {
            if ($pathAttachments === false) {
                $tmp_csr_code = tempnam("/tmp", "FOO");
                $handle = fopen($tmp_csr_code, "w");
                fwrite($handle, $orderStatus->getCsr());
                fclose($handle);

                $attachments[] = [
                    'displayname' => 'csr_code.csr',
                    'path' => $tmp_csr_code
                ];
            } else {
                $filetemp = $pathAttachments . DIRECTORY_SEPARATOR . $input['params']['serviceid']
                    . $input['params']['accountid'] . '_csr_code.csr';
                file_exists($filetemp) || touch($filetemp);
                file_put_contents($filetemp, $orderStatus->getCsr());

                $attachments[] = [
                    'displayname' => $input['params']['serviceid'] . $input['params']['accountid'] . '_csr_code.csr',
                    'filename' => $input['params']['serviceid'] . $input['params']['accountid'] . '_csr_code.csr'
                ];
            }
        }

        if ($sendCertificateTemplate == null) {
            $result = sendMessage(EmailTemplateService::SEND_CERTIFICATE_TEMPLATE_ID, $input['id'], [
                'domain' => $orderStatus['domain']
            ], false, $attachments);
        } else {
            $templateName = EmailTemplateService::getTemplateName($sendCertificateTemplate);
            $result = sendMessage($templateName, $input['id'], [
                'domain' => $orderStatus['domain']
            ], false, $attachments);
        }

        if (!empty($orderStatus->getCa())) {
            unlink($tmp_ca_code);
        }

        if (!empty($orderStatus->getCrt())) {
            unlink($tmp_crt_code);
        }

        if (!empty($orderStatus->getCsr())) {
            unlink($tmp_csr_code);
        }

        if ($result === true) {
            return [
                'success' => Lang::getInstance()->T('sendCertificateSuccess')
            ];
        }

        throw new Exception(Lang::getInstance()->T($result));
    }

    public function revalidateJSON($input, $vars = [])
    {
        $serviceId = $input['params']['serviceid'];
        $ssl = new SSLRepo();
        $sslService = $ssl->getByServiceId($serviceId);

        $newDcvMethodArray = [];
        foreach ($input['newDcvMethods'] as $domain => $method) {
            if (strpos($domain, '___') !== false) {
                $domain = str_replace('___', '*', $domain);
            }
            $newDcvMethodArray[$domain] = $method;
        }

        $data = [];

        foreach ($newDcvMethodArray as $domain => $newMethod) {
            $newdomains = [];
            $new_methods = [];

            foreach ($input['newdomains'] as $newd) {
                $newdomains[] = str_replace('___', '*', $newd);
                $new_methods[] = $newMethod;
            }

            $validationType = self::getValidationType($newMethod);
            $email = $validationType == 'EMAIL' ? $newMethod : null;

            $data[] = [
                'commonName' => $domain,
                'type' => $validationType,
                'email' => $email
            ];
        }

        try {
            ApiProvider::getInstance()->getApi(CertificatesApi::class)
                ->resendDcv($sslService->getRemoteId(), ResendDcvCollection::fromArray($data));
        } catch (Exception $ex) {
            if (strpos($ex->getMessage(), 'Function is locked for') !== false) {
                if (strpos($domain, '___') !== false) {
                    $domain = str_replace('___', '*', $domain);
                }
                $message = substr($ex->getMessage(), 0, -1) . ' for the domain: ' . $domain . '.';
            } else {
                $message = $domain . ': ' . $ex->getMessage();
            }

            return [
                'success' => 0,
                'msg' => $message
            ];
        }

        $sslorder = (array)Capsule::table('tblsslorders')->where('serviceid', $serviceId)->first();

        $sslorderconfigdata = json_decode($sslorder['configdata'], true);

        $sslorderconfigdata['dcv_method'] = $newDcvMethodArray[0];

        if ($data['new_method'] != 'email') {
            $sslorderconfigdata['approveremail'] = '';
        }

        Capsule::table('tblsslorders')->where('serviceid', $serviceId)->update([
            'configdata' => json_encode($sslorderconfigdata)
        ]);

        return [
            'success' => 1,
            'msg' => "Successfully resent DCV"
        ];
    }

    private static function getValidationType(string $type): string
    {
        if ($type == 'dns') {
            return 'DNS';
        }

        if ($type == 'http') {
            return 'FILE';
        }

        return 'EMAIL';
    }

    /**
     * @throws Exception
     */
    private static function formatDate(string $date): string {
        return  (new DateTimeImmutable($date))->format('Y-m-d H:i:s');
    }

    public function getApprovalEmailsForDomainJSON($input, $vars = [])
    {
        $serviceId = $input['id'];
        $ssl = new SSLRepo();
        $sslService = $ssl->getByServiceId($serviceId);

        return [
            'success' => 1,
            'domainEmails' => ApiProvider::getInstance()
                ->getApi(CertificatesApi::class)
                ->listDcvEmailAddresses($input['domain'], $sslService->getProductId())
        ];
    }

    public function getPrivateKeyJSON($input, $vars = [])
    {
        $sslRepo = new SSLRepo();
        $sslService = $sslRepo->getByServiceId($input['params']['serviceid']);
        $privateKey = $sslService->getPrivateKey();

        if ($privateKey = $sslService->getPrivateKey()) {
            if (!str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
                $privateKey = decrypt($privateKey);
            }

            $result = [
                'success' => 1,
                'privateKey' => $privateKey
            ];
        } else {
            $result = [
                'success' => 0,
                'message' => Lang::getInstance()->T('Can not get Private Key, please refresh page or contact support')
            ];
        }

        return $result;
    }

    public function installCertificateJSON($input, $vars = [])
    {
        $logsRepo = new LogsRepo();
        $orderRepo = new OrderRepo();
        $sslRepo = new SSLRepo();
        $sslService = $sslRepo->getByServiceId($input['params']['serviceid']);

        $details = (array)$sslService->configdata;
        $cert = $details['crt'];
        $caBundle = $details['ca'];
        $key = decrypt($details['private_key']);
        try {
            if ($details['domain']) {
                Manage::prepareDeploy($sslService->serviceid, $details['domain'], $cert, $details['csr'], $key, $caBundle);
            }

            $logsRepo->addLog(
                $sslService->userid,
                $sslService->serviceid,
                'success',
                'The certificate for the ' . $details['domain'] . ' domain has been installed correctly.'
            );
            $orderRepo->updateStatus($sslService->serviceid, 'Success');
        } catch (Exception $e) {
            $logsRepo->addLog(
                $sslService->userid,
                $sslService->serviceid,
                'error',
                '[' . $details['domain'] . '] Error: ' . $e->getMessage()
            );
            return ['success' => 0, 'message' => $e->getMessage()];
        }
        return ['success' => 1, 'message' => Lang::getInstance()->T('The certificate has been installed correctly')];
    }

    private function createAutoInvoice($productId, $service, $jsonAction = false)
    {
        if ($productId == null) {
            return null;
        }

        $product             = \WHMCS\Product\Product::where('id', '=' ,$productId)->first();
        $invoiceGenerator     = new Invoice();
        $servicesAlreadyAdded = $invoiceGenerator->checkInvoiceAlreadyCreated($service->id);
        $getInvoiceID         = false;
        if ($jsonAction) {
            $getInvoiceID = true;
        }
        $invoiceCounter = 0;
                //have product, service
        if ($servicesAlreadyAdded) {
            if ($jsonAction) {
                return [
                    'invoiceID' => $invoiceGenerator->getLatestCreatedInvoiceInfo($service->id)['invoice_id']
                ];
            }
        }

        return $invoiceGenerator->createInvoice($service, $product, $getInvoiceID);
    }
}
