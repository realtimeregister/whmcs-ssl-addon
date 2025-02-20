<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\ScriptService;
use DateTimeImmutable;
use Exception;

class AdminServicesTabFields
{
    private $p;

    public function __construct(&$params)
    {
        $this->p = &$params;
    }

    public function run()
    {
        try {
            return $this->adminServicesTabFields();
        } catch (Exception $ex) {
            return [];
        }
    }

    private function adminServicesTabFields()
    {
        $return = [];
        $return['JS/HTML'] = ScriptService::getAdminServiceScript($this->getServiceVars());
        return array_merge($return, $this->getCertificateDetails());
    }

    private function getCertificateDetails()
    {
        try {
            $ssl = new SSLRepo();
            $sslService = $ssl->getByServiceId($this->p['serviceid']);
            if (is_null($sslService)) {
                throw new Exception('Create has not been initialized');
            }

            if ($sslService->status === SSL::AWAITING_CONFIGURATION) {
                return ['Configuration Status' => SSL::AWAITING_CONFIGURATION];
            }

            if (empty($sslService->remoteid)) {
                throw new Exception('Order id does not exist');
            }

            $return = [];
            $return['Realtime Register SSL API Order ID'] = $sslService->remoteid;
            $configDataUpdate = new UpdateConfigData($sslService);
            $orderDetails = $configDataUpdate->run();

            $return['Partner Order ID'] = $orderDetails->getPartnerOrderId() ?? '-';
            $return['Configuration Status'] = $sslService->status;
            $return['Domain'] = $orderDetails->getDomain();
            $return['Order Status'] = $orderDetails->getSSLStatus();
            $return['Approver email'] = $orderDetails->getApproverEmail() ?? 'N/A';
            $return['Order Status Description'] = $orderDetails->getOrderStatusDescription();

            if ($orderDetails->getSSLStatus() === 'ACTIVE' || $orderDetails->getSSLStatus() === 'COMPLETED') {
                $return['Valid From'] = self::formatDate($orderDetails->getValidFrom());
                $return['Expires'] = self::formatDate($orderDetails->getValidTill());
            }

            if ($orderDetails->getSubscriptionEnd()) {
                $return['Subscription Starts'] = self::formatDate($orderDetails->getSubscriptionStarts());
                $return['Subscription Ends'] = self::formatDate($orderDetails->getSubscriptionEnd());
                $return['Reissue Before'] = $return['Expires'];
                unset($return['Expires']);
            }

            foreach ($orderDetails->getSanDetails() as $key => $san) {
                $return['SAN ' . ($key + 1)] = $san->san_name;
                if ($san->validation_method) {
                    if ($san->validation_method === 'email') {
                        $return['SAN ' . ($key + 1)] .= ' / ' . $san->email;
                    } else {
                        $return['SAN ' . ($key + 1)] .= ' / ' . $san->validation_method;
                    }
                }
            }

            return $return;
        } catch (Exception $ex) {
            return ['Realtime Register SSL Error' => $ex->getMessage()];
        }
    }

    private function getServiceVars()
    {
        global $CONFIG;
        $period = intval($this->p['configoptions'][ConfigOptions::OPTION_PERIOD][0]);
        $includedSans = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans = (int)$this->p['configoptions'][ConfigOptions::OPTION_SANS_COUNT];
        $sansLimit = $boughtSans + $includedSans;

        $includedSansWildcard = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSansWildcard = (int)$this->p['configoptions'][ConfigOptions::OPTION_SANS_WILDCARD_COUNT];
        $sansLimitWildcard = $boughtSansWildcard + $includedSansWildcard;

        require dirname(dirname(dirname(dirname(dirname(__DIR__))))) . DIRECTORY_SEPARATOR . 'configuration.php';

        $adminpath = 'admin';
        if (isset($customadminpath)) {
            $adminpath = $customadminpath;
        }

        return [
            'serviceid' => $this->p['serviceid'],
            'email' => $this->p['clientsdetails']['email'],
            'userid' => $this->p['userid'],
            'sansLimit' => $sansLimit,
            'sansLimitWildcard' => $sansLimitWildcard,
            'adminpath' => $adminpath,
            'version' => substr($CONFIG['Version'], 0, 1)
        ];
    }

    /**
     * @throws Exception
     */
    private static function formatDate($date): string {
        return (new DateTimeImmutable($date->date))->format('Y-m-d H:i:s');
    }
}
