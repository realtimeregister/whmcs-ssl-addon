<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Admin;
use AddonModule\RealtimeRegisterSsl\eHelpers\Invoice;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\Renew;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository as ApiConfiguration;
use DateTime;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Facades\Date;
use WHMCS\Service\Service;

class ExpiryHandler extends BaseTask
{
    protected $defaultFrequency = 1440;
    protected $skipDailyCron = false;
    protected $defaultPriority = 4200;
    protected $defaultDescription = 'Expiry handler, send notifications, creates invoices and renews certificates based 
    configuration.';
    protected $defaultName = 'Expiry Handler';
    protected $outputs = ["sent" => ["defaultValue" => 0, "identifier" => "sent", "name" => "Emails Sent"]];
    protected $icon = "fas fa-envelope";
    protected $successCountIdentifier = "sent";
    protected $successKeyword = "Emails Sent";

    public function __invoke()
    {
        if ($this->enabledTask('cron_renewal')) {
            logActivity("Realtime Register SSL: Notifier started");
            Addon::I();

            $apiConf = (new ApiConfiguration())->get();

            $renewWithinOnetime = (int)$apiConf->renew_invoice_days_one_time;
            $renewWithinRecurring = (int)$apiConf->renew_invoice_days_recurring;

            $send_expiration_notification_recurring = (bool)$apiConf->send_expiration_notification_recurring;
            $send_expiration_notification_one_time = (bool)$apiConf->send_expiration_notification_one_time;

            $createAutoInvoice = (bool)$apiConf->auto_renew_invoice_recurring;
            $autoRenewSetting = $apiConf->autorenew_ordertype;

            $this->sslRepo = new SSLRepo();

            //get all completed ssl orders
            $sslOrders = $this->getSSLOrders([SSL::PENDING_INSTALLATION, SSL::ACTIVE, SSL::FAILED_INSTALLATION]);

            $emailSendsCount = 0;
            $emailSendsCountReissue = 0;

            foreach ($sslOrders as $sslOrder) {
                $serviceId = $sslOrder->serviceid;
                $service = Capsule::table('tblhosting')->where('id', $serviceId)->first();
                $daysLeft = -1;
                $daysReissue = -1;

                //get days left to expire
                $sslOrder = Capsule::table('tblsslorders')->where('serviceid', $serviceId)->first();
                $configData = json_decode($sslOrder->configdata);
                if ($configData->end_date?->date) {
                    $daysLeft = $this->checkOrderExpiryDate(new DateTime($configData->end_date->date));
                    $daysReissue = $this->checkOrderExpiryDate(new DateTime($configData->valid_till->date));
                } else if ($configData->valid_till?->date) {
                    $daysLeft = $this->checkOrderExpiryDate(new DateTime($configData->valid_till->date));
                }

                $product = Capsule::table('tblproducts')->where('id', $service->packageid)->first();

                if ($daysReissue == 30) {
                    // send email
                    $emailSendsCountReissue += $this->sendReissueNotifyEmail($serviceId);
                }

                if ($daysLeft < 0) {
                    continue;
                }

                if (
                    in_array($daysLeft, self::getExpiryMailRange($renewWithinRecurring))
                    && $service->billingcycle != 'One Time'
                    && $send_expiration_notification_recurring
                ) {
                    $emailSendsCount += $this->sendExpireNotifyEmail($serviceId, $daysLeft);
                }

                if (
                    in_array($daysLeft, self::getExpiryMailRange($renewWithinOnetime))
                    && $service->billingcycle == 'One Time'
                    && $send_expiration_notification_one_time
                ) {
                    $emailSendsCount += $this->sendExpireNotifyEmail($serviceId, $daysLeft);
                }

                // Handle auto-renew based on settings
                if ($daysLeft < $renewWithinRecurring && $service->billingcycle != 'One Time') {
                    $this->handleAutoRenew($service, $product, $createAutoInvoice, $autoRenewSetting);
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

    private static function getExpiryMailRange($renewWithin)
    {
        return array_filter([0, 1, 3, 7, 14, 21, 30], fn($daysLeft) => $daysLeft <= $renewWithin);
    }

    /**
     * @throws \Exception
     */
    private function handleAutoRenew($service, $product, $createAutoInvoice, $autoRenewSetting)
    {
        if (!$product) {
            return;
        }

        try {
            $invoiceGenerator = new Invoice();
            if ($createAutoInvoice && !$invoiceGenerator->checkInvoiceAlreadyCreated($service->id)) {
                $invoiceGenerator->createInvoice($service, $product);
            }

            if ($autoRenewSetting == 'renew_always') {
                $params = ['serviceid' => $service->id,
                    'userid' => $service->userid,
                    ConfigOptions::API_PRODUCT_ID => $product->{ConfigOptions::API_PRODUCT_ID},
                    ConfigOptions::AUTH_KEY_ENABLED => $product->{ConfigOptions::AUTH_KEY_ENABLED}
                ];
                (new Renew($params))->run();
            }
        } catch (\Exception $e) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS Notifier: 
                Error while renewing SSL certificate (service ' . $service->id . '): ' . $e->getMessage()
            );
            throw $e;
        }
    }

    private function checkOrderExpiryDate(DateTime $expiryDate): int
    {
        $today = new DateTime();

        $diff = $expiryDate->diff($today);
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
            'id' => $serviceId,
            'messagename' => EmailTemplateService::REISSUE_TEMPLATE_ID,
        ];

        $adminUserName = Admin::getAdminUserName();

        $results = localAPI($command, $postData, $adminUserName);

        $resultSuccess = $results['result'] == 'success';
        if (!$resultSuccess) {
            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS Notifier: Error while sending customer notifications (service '
                . $serviceId . '): ' . $results['message']
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
                'Realtime Register SSL WHMCS Notifier: Error while sending customer notifications (service '
                . $serviceId . '): ' . $results['message']
            );
        }
        return $resultSuccess;
    }
}
