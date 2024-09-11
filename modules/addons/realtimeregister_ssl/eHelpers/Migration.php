<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

define('DS', DIRECTORY_SEPARATOR);
define('WHMCS_MAIN_DIR', substr(dirname(__FILE__), 0, strpos(dirname(__FILE__), 'modules' . DS . 'addons')));

use Exception;
use Illuminate\Database\Capsule\Manager as Capsule;

class Migration
{
    private static $instance;
    private static $moduleLoaded = false;
    const MODULE_NAME                  = 'realtimeregister_ssl';

    public function __construct()
    {
        if ($this->isModuleExist(self::MODULE_NAME)) {
            $moduleLoaderPath = WHMCS_MAIN_DIR . DS . 'modules' . DS . 'addons' . DS . self::MODULE_NAME
                . DS . 'Loader.php';
            if (file_exists($moduleLoaderPath)) {
                require_once $moduleLoaderPath;
                new \AddonModule\RealtimeRegisterSsl\Loader();
                self::$moduleLoaded = true;
            }
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Migration();
        }
        return self::$instance;
    }

    public function run()
    {
        try {
            \AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Data & Configuration Migration Started'
            );
            
            #import:
            #configuration
            $this->importModuleConfiguration();
            #invoices_info
            $this->importInvoicesInfo();
            #update products
            $this->updateExistingProducts();
            #update SSL orders
            $this->updateSSLOrders();
            
            \AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Data & Configuration Migration Completed'
            );
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function importModuleConfiguration()
    {
        if (self::$moduleLoaded) {
            $apiConfigRepo = new \AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository();
            $input         = (array) $apiConfigRepo->get();

            $apiConfigRepo = new \AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository();
            $apiConfigRepo->setConfiguration($input);
            
            \AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Module '.self::MODULE_NAME.' configuration imported successfully.'
            );
        } else {
            \AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Module '.self::MODULE_NAME.' installation not detected.'
            );
        }
    }

    private function importInvoicesInfo()
    {
        if (self::$moduleLoaded) {
            $oldTableName = Invoice::INVOICE_INFOS_TABLE_NAME;
            $newTableName = \AddonModule\RealtimeRegisterSsl\eHelpers\Invoice::INVOICE_INFOS_TABLE_NAME;

            $fields = [
                'user_id',
                'invoice_id',
                'service_id',
                'product_id',
                'order_id',
                'new_service_id',
                'status',
                'created_at',
                'updated_at',
            ];
            $query  = 'INSERT INTO  `' . $newTableName . '` SELECT `' . $oldTableName . '`.* FROM `' . $oldTableName
                . '` ON DUPLICATE KEY UPDATE ';
            foreach ($fields as $i => $field) {
                $query .= ($i > 0 ? ', ' : '') . $field . '=VALUES(' . $field . ')';
            }

            \AddonModule\RealtimeRegisterSsl\addonLibs\MySQL\Query::query($query);
        }
    }

    private function updateExistingProducts()
    {

        $productsRepo = new \AddonModule\RealtimeRegisterSsl\models\whmcs\product\Products();
        $productsRepo->onlyModule(self::MODULE_NAME);

        foreach ($productsRepo->get() as $product) {
            $product->setServerType(\AddonModule\RealtimeRegisterSsl\Addon::I()->configuration()->systemName);
            $product->save();
            
            \AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: Product ID: ' . $product->id
                . ' has been modified. Module changed from ' . self::MODULE_NAME
                . ' to ' . \AddonModule\RealtimeRegisterSsl\Addon::I()->configuration()->systemName
            );
        }
    }

    private function updateSSLOrders()
    {
        $SSLOrders = new \AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $orders    = $SSLOrders->getBy(['module' => self::MODULE_NAME]);

        foreach ($orders as $ssl) {
            Capsule::table('tblsslorders')
                    ->where('id', '=', $ssl->id)
                    ->where('remoteid', '=', $ssl->remoteid)
                    ->update([
                        'module' => \AddonModule\RealtimeRegisterSsl\Addon::I()->configuration()->systemName
                    ]);
            
            \AddonModule\RealtimeRegisterSsl\eHelpers\Whmcs::savelogActivityRealtimeRegisterSsl(
                'Realtime Register SSL WHMCS: SSL Service ID: ' . $ssl->serviceid
                . ' has been modified. Module changed from ' . self::MODULE_NAME . ' to '
                . \AddonModule\RealtimeRegisterSsl\Addon::I()->configuration()->systemName
            );
        }
    }

    private function isModuleExist()
    {
        $result = \AddonModule\RealtimeRegisterSsl\addonLibs\MySQL\Query::select(
            ['value'],
            'tblconfiguration',
            ['setting' => 'ActiveAddonModules']
        );

        while ($row = $result->fetch()) {
            $activeModules = $row['value'];
        }

        $activeModulesArray = explode(',', $activeModules);
        foreach ($activeModulesArray as $module) {
            if ($module == self::MODULE_NAME) {
                return true;
            }
        }

        return false;
    }
}
