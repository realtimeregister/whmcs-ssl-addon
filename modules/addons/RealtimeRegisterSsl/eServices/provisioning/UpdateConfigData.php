<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

use MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use WHMCS\Database\Capsule;

class UpdateConfigData
{
    private $sslService;
    private $orderdata;
    
    public function __construct($sslService, $orderdata = array())
    {
        $this->sslService = $sslService;
        $this->orderdata = $orderdata;
    }
    
    public function run() {
        try {
            return $this->updateConfigData();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }
        return 'success';
    }
    
    public function updateConfigData()
    {
        if(!isset($this->sslService->remoteid) || empty($this->sslService->remoteid))
        {
            return;
        }

        if(empty($this->orderdata))
        {
            $order = \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getOrderStatus($this->sslService->remoteid);
        }
        else
        {
            $order = $this->orderdata;
        }
           
        $apiRepo = new Products();
        
        if(!isset($this->sslService->configdata->product_brand) || empty($this->sslService->configdata->product_brand))
        {
            $checkTable = Capsule::schema()->hasTable(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND);
            
            $brandName = null;
            if($checkTable !== false)
            {
                $productData = Capsule::table(Products::MGFW_REALTIMEREGISTERSSL_PRODUCT_BRAND)->where('pid', $order['product_id'])->first();
                
                if(isset($productData->brand) && !empty($productData->brand))
                {
                    $brandName = $productData->brand;
                }
                
            }
            
            if($brandName === null)
            {
                $apiProduct = $apiRepo->getProduct($order['product_id']);
                $apiProduct->brand = $brandName;
            }
        }

//        if (($order['status'] != 'expired') && ($order['status'] != 'cancelled'))
//        {
            $sslOrder = $this->sslService;

            $sslOrder->setCa($order['ca_code']);
            $sslOrder->setCrt($order['crt_code']);    
            $sslOrder->setPartnerOrderId($order['partner_order_id']);

            $sslOrder->setValidFrom($order['valid_from']);
            $sslOrder->setValidTill($order['valid_till']);

            $sslOrder->setSubscriptionStarts($order['begin_date']);
            $sslOrder->setSubscriptionEnds($order['end_date']);

            $sslOrder->setDomain($order['domain']);
            $sslOrder->setSSLStatus($order['status']);
            $sslOrder->setOrderStatusDescription($order['status_description']);

            $sslOrder->setApproverMethod($order['approver_method']);
            $sslOrder->setDcvMethod($order['dcv_method']);
            $sslOrder->setProductId($order['product_id']);
            $sslOrder->setSSLTotalDomains($order['total_domains']);
            
            if(!isset($this->sslService->configdata->product_brand) || empty($this->sslService->configdata->product_brand))
            {
                $sslOrder->setProductBrand($brandName);
            }
            
            $sslOrder->setSanDetails($order['san']);
            $sslOrder->setConfigdataKey("approveremail", $order['approver_email']);

            $sslOrder->save();
       // }

        return $order;
    }
}
