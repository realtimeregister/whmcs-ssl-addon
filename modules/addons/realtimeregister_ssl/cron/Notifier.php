<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\eHelpers\Admin;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\Renew;
use DateTime;
use Illuminate\Database\Capsule\Manager as Capsule;
use RealtimeRegister\Api\CertificatesApi;
use WHMCS\Service\Service;

class Notifier extends BaseTask
{
    protected $defaultFrequency = 0;
    protected $skipDailyCron = false;
    protected $defaultPriority = 4200;
    protected $defaultDescription = 'Send customers notifications of expiring services and create renewal invoices ' .
    'for services that expire within the selected number of days';
    protected $defaultName = 'Certificate notifier';
    protected $outputs = ["sent" => ["defaultValue" => 0, "identifier" => "sent", "name" => "Emails Sent"]];
    protected $icon = "fas fa-envelope";
    protected $successCountIdentifier = "sent";
    protected $successKeyword = "Emails Sent";

    public function __invoke()
    {
        if ($this->enabledTask('cron_renewal')) {
            logActivity("Realtime Register SSL: Notifier started");

            //get renewal settings
            $apiConf = (new \AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository())->get();
            //get saved amount days to generate invoice (one time & reccuring)
            $renew_invoice_days_one_time = $apiConf->renew_invoice_days_one_time;
            $renew_invoice_days_recurring = $apiConf->renew_invoice_days_recurring;

            $send_expiration_notification_reccuring = (bool)$apiConf->send_expiration_notification_reccuring;
            $send_expiration_notification_one_time = (bool)$apiConf->send_expiration_notification_one_time;

            $createAutoInvoice = (bool)$apiConf->auto_renew_invoice_recurring;
            $autoRenewSetting = 'always_renew';

            $this->sslRepo = new SSLRepo();

            //get all completed ssl orders
            $sslOrders = $this->getSSLOrders();

            $synchServicesId = [];
            foreach ($sslOrders as $row) {
                $synchServicesId[] = $row->serviceid;
            }

            $emailSendsCount = 0;
            $emailSendsCountReissue = 0;

            foreach ($synchServicesId as $serviceid) {
                $srv = Capsule::table('tblhosting')->where('id', $serviceid)->first();

                //get days left to expire from WHMCS
                $daysLeft = $this->checkOrderExpiryDate(new DateTime($srv->nextduedate));
                $daysReissue = $this->checkReissueDate($srv->id);

                /*
                 * if service is One Time and nextduedate is set as 0000-00-00 get valid
                 * till from Realtime Register Ssl API
                 */
                if ($srv->billingcycle == 'One Time') {
                    $sslOrder = Capsule::table('tblsslorders')->where('serviceid', $srv->id)->first();

                    if (!empty($sslOrder->remoteid)) {
                        /** @var CertificatesApi $sslOrderApi */
                        $sslOrderApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
                        $ssl = $sslOrderApi->listCertificates(1, null, null, ['process:eq' => $sslOrder->remoteid]);
                        if ($ssl[0]) {
                            $daysLeft = $this->checkOrderExpiryDate($ssl[0]->expiryDate);
                        }
                    }
                }

                $product = Capsule::table('tblproducts')->where('id', $srv->packageid)->first();

                if ($srv->domainstatus == 'Active' && $daysReissue == '30' && $product->configoption2 > 12) {
                    // send email
                    $emailSendsCountReissue += $this->sendReissueNotifyEmail($srv->id);
                }

                //service was synchronized, so we can base on nextduedate, that should be the same as valid_till
                //$daysLeft = 90;
                if ($daysLeft >= 0) {
                    if ($srv->billingcycle == 'One Time' && $send_expiration_notification_one_time
                        || $srv->billingcycle != 'One Time' && $send_expiration_notification_reccuring
                    ) {
                        $emailSendsCount += $this->sendExpireNotifyEmail($srv->id, $daysLeft);
                    }
                }

                $savedRenewDays = $renew_invoice_days_recurring;

                //if it is proper amount of days before expiry, we create invoice
                if ($daysLeft >= 0
                    && $daysLeft < (int)$savedRenewDays
                    && $srv->billingcycle != 'One Time') {
                    $this->handleAutoRenew($serviceid, $product, $createAutoInvoice, $autoRenewSetting);
                }
            }

            logActivity('Notifier completed. Number of emails send: ' . $emailSendsCount, 0);

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Notifier completed. Number of emails sent: " . $emailSendsCount
            );

            $this->output("sent")->write($emailSendsCount + $emailSendsCountReissue);
        }


        return $this;
    }

    /**
     * @throws \Exception
     */
    private function handleAutoRenew($serviceId, $product, $createAutoInvoice, $autoRenewSetting)
    {
        try {
            $invoiceGenerator = new Invoice();
            $service = Service::where('id', $serviceId)->first();
            if ($createAutoInvoice) {
                $invoiceGenerator->createInvoice($service, $product);
            }
            if ($autoRenewSetting == 'always_renew') {
                $params = ['serviceid' => $serviceId,
                    'userid' => $service->client()->id,
                    ConfigOptions::API_PRODUCT_ID => $product->{ConfigOptions::API_PRODUCT_ID}];
                (new Renew($params))->run();
            }
        } catch (\Exception $e) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS Notifier: 
                Error while renewing SSL certificate (service ' . $serviceId . '): ' . $e->getMessage()
            );
            throw $e;
        }
    }

    private function checkOrderExpiryDate($expiryDate): int
    {
        $today = new DateTime();

        $diff = $expiryDate->diff($today, false);
        if ($diff->invert == 0) {
            // Date is in the past
            return -1;
        }

        return $diff->days;
    }

    private function sendReissueNotifyEmail($serviceId): bool
    {
        $command = 'SendEmail';

        $postData = [
            'serviceid' => $serviceId,
            'messagename' => EmailTemplateService::REISSUE_TEMPLATE_ID,
        ];

        $adminUserName = Admin::getAdminUserName();

        $results = localAPI($command, $postData, $adminUserName);

        $resultSuccess = $results['result'] == 'success';
        if (!$resultSuccess) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS Notifier: Error while sending customer notifications (service ' . $serviceId . '): ' . $results['message']
            );
        }
        return $resultSuccess;
    }

    private function sendExpireNotifyEmail($serviceId, $daysLeft): bool
    {
        $command = 'SendEmail';

        $postData = [
            'id' => $serviceId,
            'messagename' => EmailTemplateService::EXPIRATION_TEMPLATE_ID,
            'customvars' => base64_encode(serialize(["expireDaysLeft" => $daysLeft])),
        ];

        $adminUserName = Admin::getAdminUserName();

        $results = localAPI($command, $postData, $adminUserName);

        $resultSuccess = $results['result'] == 'success';
        if (!$resultSuccess) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS Notifier: Error while sending customer notifications (service ' . $serviceId . '): ' . $results['message']
            );
        }
        return $resultSuccess;
    }

    private function checkReissueDate($serviceid): float|bool|int
    {
        $sslOrder = Capsule::table('tblsslorders')->where('serviceid', $serviceid)->first();

        if (!empty($sslOrder->configdata)) {
            $configdata = json_decode($sslOrder->configdata, true);

            if (!empty($configdata['end_date'])) {
                $now = strtotime(date('Y-m-d'));
                $end_date = strtotime($configdata['valid_till']['date']);
                $datediff = $now - $end_date;

                $nextReissue = abs(round($datediff / (60 * 60 * 24)));
                return $nextReissue;
            }
        }
        return false;
    }
}
