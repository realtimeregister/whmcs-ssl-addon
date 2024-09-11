<?php

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\service;

use AddonModule\RealtimeRegisterSsl\mgLibs\exceptions\System;
use AddonModule\RealtimeRegisterSsl\mgLibs\models\Orm;
use AddonModule\RealtimeRegisterSsl\mgLibs\MySQL\Query;
use AddonModule\RealtimeRegisterSsl\models\whmcs\clients\Client;
use AddonModule\RealtimeRegisterSsl\models\whmcs\orders\Order;
use AddonModule\RealtimeRegisterSsl\models\whmcs\product\Product;
use AddonModule\RealtimeRegisterSsl\models\whmcs\servers\Server;
use stdClass;

/**
 *
 * Dit.txt
 * dit.txt
 */

/**
 * Description of account
 * @Table(name=tblhosting,preventUpdate,prefixed=false)
 * @SuppressWarnings(PHPMD)
 */
class Service extends Orm
{
    /**
     * @Column()
     * @var type
     */
    public $id;

    /**
     * @Column(name=userid,as=userid)
     * @var int
     */
    public $clientID = 0;

    /**
     *
     * @var client
     */
    private $_client;

    /**
     * @Column(name=dedicatedip,as=dedicatedip)
     * @var string
     */
    public $dedicatedIP = null;

    /**
     * @Column(name=assignedips,as=assingedips)
     * @var array
     */
    public $IPList = [];

    /**
     *
     * @Column(name=server,as=serverid)
     * @var int
     */
    public $serverID;

    /**
     *
     * @var server
     */
    private $_server;

    /**
     *
     * @Column(name=packageid,as=pid)
     * @var int
     */
    public $productID;

    /**
     *
     * @var product
     */
    private $_product;

    /**
     *
     * @var order
     */
    private $_order;

    /**
     *
     * @Column()
     * @var string
     */
    public $domain;

    /**
     *
     * @Column()
     * @var string
     */
    public $username;

    /**
     *
     * @Column(as=passwordEncrypted)
     * @var string
     */
    public $password;

    /**
     *
     * @Column(name=nextduedate=nextDueDate)
     * @var string
     */
    public $nextDueDate;

    /**
     *
     * @Column(name=firstpaymentamount)
     * @var decimal
     */
    public $firstpaymentamount;

    /**
     *
     * @Column(name=amount)
     * @var float
     */
    public $amount;

    /**
     *
     * @Column(name=orderid)
     * @var int
     */
    protected $_orderid;

    /**
     *
     * @Column(name=domainstatus,as=_domainstatus)
     * @var string
     */
    protected $_status;

    /**
     *
     * @Column(name=billingcycle,as=_billingcycle)
     * @var string
     */
    protected $_billingcycle;

    /**
     *
     * @var \AddonModule\RealtimeRegisterSsl\models\whmcs\service\customFields\Repository
     */
    private $_customFields;

    /**
     *
     * @var \AddonModule\RealtimeRegisterSsl\models\whmcs\service\configOptions\Repository
     */
    private $_configOptions;

    /**
     * Load Account
     *
     * @param int $id
     * @param array $data
     * @throws \Exception
     */
    public function __construct($id = null, $data = [])
    {
        $this->id = $id;
        $this->load($data);
    }

    /**
     * Load Account Server
     * Function allows to easy overwrite server object
     *
     * @param int $id
     * @param array $data
     * @return server
     */
    protected function loadServer($id, $data = [])
    {
        return new Server($id, $data);
    }

    /**
     * Load Product
     * Function allows to easy overwrite product object
     *
     * @param array $data
     * @return product
     */
    protected function loadProduct($data = [])
    {
        return new Product($this->productID, $data);
    }

    /**
     * Load Order
     * Function allows to easy overwrite order object
     *
     * @param array $data
     * @return order
     */
    protected function loadOrder($data = [])
    {
        return new Order($this->orderId(), $data);
    }

    /**
     * Load Client
     * Function allows to easy overwrite product object
     *
     * @return Client
     */
    protected function loadClient($data = [])
    {
        return new Client($this->clientID, $data);
    }

    /**
     * Get Server Connected With Service
     *
     * @return server
     */
    public function server()
    {
        if (empty($this->_server)) {
            $this->_server = $this->loadServer($this->serverID);
        }
        return $this->_server;
    }

    /**
     * Get Client Connected with Service
     *
     * @return client
     */
    public function client()
    {
        if (empty($this->_client)) {
            $this->_client = $this->loadClient();
        }

        return $this->_client;
    }

    /**
     * Get Product Service
     *
     * @return product
     */
    public function product()
    {
        if (empty($this->_product)) {
            $this->_product = $this->loadProduct();
        }
        return $this->_product;
    }

    /**
     * Get Order Service
     *
     * @return order
     */
    public function order()
    {
        if (empty($this->_order)) {
            $this->_order = $this->loadOrder();
        }
        return $this->_order;
    }

    /**
     * Get Custom Fields
     *
     * @return customFields\repository
     */
    public function customFields()
    {
        if (empty($this->_customFields)) {
            $this->_customFields =
                new \AddonModule\RealtimeRegisterSsl\models\whmcs\service\customFields\Repository($this->id);
        }

        return $this->_customFields;
    }

    /**
     * Get Config Options
     *
     * @return configOptions
     */
    public function configOptions()
    {
        if (empty($this->_configOptions)) {
            $this->_configOptions =
                new \AddonModule\RealtimeRegisterSsl\models\whmcs\service\configOptions\Repository($this->id);
        }

        return $this->_configOptions;
    }

    /**
     * Get Merged Configs from product configuration & custom fields & confi optins
     *
     * @return stdClass
     */
    public function mergedConfig()
    {
        $obj = new stdClass();

        foreach ($this->product()->configuration as $name => $value) {
            if (!empty($value)) {
                $obj->$name = $value;
            }
        }

        foreach ($this->customFields()->toArray(false) as $name => $value) {
            if (!empty($value)) {
                $obj->$name = $value;
            }
        }

        foreach ($this->configOptions()->toArray(false) as $name => $value) {
            if (!empty($value)) {
                $obj->$name = $value;
            }
        }

        return $obj;
    }

    /**
     * Save Account Settings
     *
     */
    public function save($cols = [])
    {
        $cols['password'] = encrypt($this->password);

        if (($key = array_search($this->dedicatedIP, $this->IPList)) !== false) {
            unset($this->IPList[$key]);
        }

        $cols['assignedips'] = implode("\n", $this->IPList);

        parent::save($cols);
    }

    /**
     * Set Object Properties
     *
     * @param array $data
     * @throws system
     */
    public function load(array $data = [])
    {
        if (empty($this->id) && !empty($data['serviceid'])) {
            $this->id = $data['serviceid'];
        }

        if ($this->id !== null && empty($data)) {
            $data = query::select(
                static::fieldDeclaration(),
                static::tableName(),
                [
                    'id' => $this->id
                ]
            )->fetch();

            if (empty($data)) {
                throw new system(
                    'Unable to find ' . get_class($this) . ' with ID:' . $this->id
                );
            }
        }

        if (isset($data['passwordEncrypted'])) {
            $data['password'] = decrypt($data['passwordEncrypted']);
        }

        if (!empty($data['dedicatedip'])) {
            $this->dedicatedIP = $this->IPList[] = $data['dedicatedip'];
        }
        if (!empty($data['assingedips'])) {
            foreach (explode("\n", $data['assingedips']) as $ip) {
                if ($ip) {
                    $this->IPList[] = $ip;
                }
            }
        }

        if (!empty($data['_domainstatus'])) {
            $this->_status = $data['_domainstatus'];
            $this->_billingcycle = $data['_billingcycle'];
        }

        if (!empty($data['userid'])) {
            $this->clientID = $data['userid'];
            $this->serverID = $data['serverid'];
            $this->domain = $data['domain'];
            $this->productID = $data['pid'];
            $this->username = $data['username'];
            $this->password = $data['password'];
            $this->amount = $data['amount'];
            $this->firstpaymentamount = $data['firstpaymentamount'];
        }


        if (!empty($data['server'])) {
            $this->_server = $this->loadServer($data['serverid'], [
                'hostname' => $data['serverhostname'],
                'username' => $data['serverusername'],
                'password' => $data['serverpassword'],
                'accesshash' => $data['serveraccesshash'],
                'secure' => $data['serversecure'],
                'ip' => $data['serverip']
            ]);
        }

        if (!empty($data['customfields'])) {
            $this->_customFields = new \AddonModule\RealtimeRegisterSsl\models\whmcs\service\customFields\Repository(
                $this->id,
                $data['customfields']
            );
        }

        if (!empty($data['configoptions'])) {
            $this->_configOptions = new configOptions\Repository($this->id);
        }

        if (!empty($data['_orderid'])) {
            $this->_orderid = $data['_orderid'];
        }

        if (!empty($data['nextDueDate'])) {
            $this->nextDueDate = $data['nextDueDate'];
        }
    }


    public function billingcycle()
    {
        if (empty($this->_billingcycle)) {
            $this->load();
        }

        return $this->_billingcycle;
    }

    public function getBillingCycleNumMonth(): int
    {
        switch ($this->billingcycle()) {
            case 'Monthly':
                return 1;
            case 'Quarterly':
                return 3;
            case 'Semi-Annually':
                return 6;
            case 'Annually':
                return 12;
            case 'Biennially':
                return 24;
            case 'Triennially':
                return 36;
        }

        return 0;
    }

    public function status()
    {
        if (empty($this->_status)) {
            $this->load();
        }
        return $this->_status;
    }

    public function orderId()
    {
        if (empty($this->_orderid)) {
            $this->load();
        }
        return $this->_orderid;
    }

    public function getDomain()
    {
        if (empty($this->domain)) {
            return null;
        }

        return $this->domain;
    }

    public function getNextDueDate()
    {
        if (empty($this->nextDueDate) || $this->nextDueDate == '0000-00-00') {
            return null;
        }

        return $this->nextDueDate;
    }

    public function getFirstPaymentAmount()
    {
        return $this->firstpaymentamount;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getID()
    {
        return $this->id;
    }
}
