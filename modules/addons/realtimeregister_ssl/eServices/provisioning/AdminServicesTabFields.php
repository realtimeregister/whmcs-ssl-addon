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

            $sslData = [];
            $sslData['Realtime Register SSL API Order ID'] = $sslService->remoteid;
            $configDataUpdate = new UpdateConfigData($sslService);

            $orderDetails = $configDataUpdate->run();
            if (!$orderDetails) {
                return $sslData;
            }

            $sslData['Partner Order ID'] = $orderDetails->getPartnerOrderId() ?? '-';
            $sslData['Configuration Status'] = $sslService->status;
            $sslData['Domain'] = $orderDetails->getDomain();
            $sslData['Order Status'] = $orderDetails->getSSLStatus();
            $sslData['Approver email'] = $orderDetails->getApproverEmail() ?? 'N/A';
            $sslData['Order Status Description'] = $orderDetails->getOrderStatusDescription();

            if ($orderDetails->getSSLStatus() === 'ACTIVE' || $orderDetails->getSSLStatus() === 'COMPLETED') {
                $sslData['Valid From'] = self::formatDate($orderDetails->getValidFrom());
                $sslData['Expires'] = self::formatDate($orderDetails->getValidTill());
            }

            if ($orderDetails->getSubscriptionEnd()) {
                $sslData['Subscription Starts'] = self::formatDate($orderDetails->getSubscriptionStarts());
                $sslData['Subscription Ends'] = self::formatDate($orderDetails->getSubscriptionEnd());
                $sslData['Reissue Before'] = $sslData['Expires'];
                unset($sslData['Expires']);
            }

            foreach ($orderDetails->getSanDetails() as $key => $san) {
                $sslData['SAN ' . ($key + 1)] = $san->san_name;
                if ($san->validation_method) {
                    if ($san->validation_method === 'email') {
                        $sslData['SAN ' . ($key + 1)] .= ' / ' . $san->email;
                    } else {
                        $sslData['SAN ' . ($key + 1)] .= ' / ' . $san->validation_method;
                    }
                }
            }

            return $sslData;
        } catch (Exception $ex) {
            return ['Realtime Register SSL Error' => $ex->getMessage()];
        }
    }

    private function getServiceVars()
    {
        global $CONFIG;
        $includedSans = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS];
        $boughtSans = (int)$this->p['configoptions'][ConfigOptions::OPTION_SANS_COUNT];
        $sansLimit = $boughtSans + $includedSans;

        $includedSansWildcard = (int)$this->p[ConfigOptions::PRODUCT_INCLUDED_SANS_WILDCARD];
        $boughtSansWildcard = (int)$this->p['configoptions'][ConfigOptions::OPTION_SANS_WILDCARD_COUNT];
        $sansLimitWildcard = $boughtSansWildcard + $includedSansWildcard;

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
