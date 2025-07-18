<?php

namespace AddonModule\RealtimeRegisterSsl\cron;

use AddonModule\RealtimeRegisterSsl\Addon;
use AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\KeyToIdMapping;
use AddonModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL as SSLRepo;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\UpdateConfigData;
use RealtimeRegister\Api\CertificatesApi;
use RealtimeRegister\Domain\Product;
use RealtimeRegister\Domain\ProductCollection;
use Illuminate\Database\Capsule\Manager as Capsule;

class CertificateDetailsUpdater extends BaseTask
{
    protected $skipDailyCron = false;
    protected $defaultPriority = 4200;
    protected $defaultName = "Certificate details updater";

    public function __invoke()
    {
        if ($this->enabledTask('cron_certificate_details_updater')) {
            logActivity('Realtime Register SSL: Certificate details updater');
            Addon::I();

            Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Certificate Details Updating started.'
            );

            $this->sslRepo = new SSLRepo();

            $checkTable = Capsule::schema()->hasTable(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND);
            if ($checkTable === false) {
                Capsule::schema()->create(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND, function ($table) {
                    $table->increments('id');
                    $table->integer('pid');
                    $table->string('pid_identifier');
                    $table->string('brand');
                    $table->text('data');
                });
            }

            Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)->truncate();

            $certificatedApi = ApiProvider::getInstance()->getApi(CertificatesApi::class);
            $i = 0;
            /** @var ProductCollection $apiProducts */
            while ($apiProducts = $certificatedApi->listProducts(10, $i)) {
                /** @var Product $apiProduct */
                foreach ($apiProducts->toArray() as $apiProduct) {
                    Capsule::table(Products::REALTIMEREGISTERSSL_PRODUCT_BRAND)->insert([
                        'pid' => KeyToIdMapping::getIdByKey($apiProduct['product']),
                        'pid_identifier' => $apiProduct['product'],
                        'brand' => $apiProduct['brand'],
                        'data' => json_encode($apiProduct)
                    ]);
                }
                $i += 10;

                $total = $apiProducts->pagination->total;
                if ($total < $i) {
                    break;
                }
            }

            $sslOrders = $this->getSSLOrders();

            foreach ($sslOrders as $sslService) {
                $sslService = \AddonModule\RealtimeRegisterSsl\eModels\whmcs\service\SSL::hydrate([$sslService])[0];

                $configDataUpdate = new UpdateConfigData($sslService);
                $configDataUpdate->run();
            }

            Whmcs::savelogActivityRealtimeRegisterSsl(
                "Realtime Register SSL WHMCS: Certificate Details Updating completed."
            );
        }
    }
}
