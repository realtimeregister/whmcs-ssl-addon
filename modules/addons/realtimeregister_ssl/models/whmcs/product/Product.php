<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\product;

use MGModule\RealtimeRegisterSsl\mgLibs\models\Orm;
use MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query;
use MGModule\RealtimeRegisterSsl\models\service\server;
use MGModule\RealtimeRegisterSsl\models\whmcs\customFields\Repository;

/**
 * Description of product
 *
 * @Table(name=tblproducts,preventUpdate,prefixed=false)
 * @author Michal Czech <michael@modulesgarden.com>
 */
class Product extends Orm
{
    /**
     *
     * @Column(int)
     * @var int
     */
    public $id;

    /**
     *
     * @Column()
     * @var string
     */
    public $type;

    /**
     *
     * @Column(int)
     * @var int
     */
    public $gid;

    /**
     * @Column()
     * @var string
     */
    public $name;
    /**
     *
     * @Column(int)
     * @var int
     */
    public $showdomainoptions;
    /**
     * @Column(name=servertype)
     * @var string
     */
    public $serverType;

    /**
     * @Column(name=paytype)
     * @var string
     */
    public $paytype;

    /**
     * @Column(name=servergroup)
     * @var int
     */
    public $serverGroupID;

    /**
     *
     * @var server
     */
    private $_server;

    /**
     *
     * @var configuration
     */
    private $_configuration;

    /**
     *
     * @var Repository
     */
    private $_customFields;


    /**
     * Create Product
     *
     * @param int $id
     * @param array $params
     * @author Michal Czech <michael@modulesgarden.com>
     */
    public function __construct($id = null, $params = [])
    {
        $this->id = $id;
        $this->load($params);
    }

    /**
     * Load Product
     *
     * @param array $params
     * @author Michal Czech <michael@modulesgarden.com>
     */
    public function load($params = [])
    {
        if (empty($params)) {
            $fields = static::fieldDeclaration();

            for ($i = 1; $i < 25; $i++) {
                $fields['configoption' . $i] = 'configoption' . $i;
            }

            $params = Query::select(
                $fields,
                static::tableName(),
                [
                    'id' => $this->id
                ]
            )->fetch();
        }

        $this->fillProperties($params);

        if (isset($params['serverGroupID'])) {
            $this->serverGroupID = $params['serverGroupID'];
        }

        if (isset($params['configoption1'])) {
            $this->_configuration = $this->loadConfiguration($params);
        }
    }

    /**
     * Load Server
     *
     * @return server
     */
    protected function loadServer()
    {
        if (empty($this->serverGroupID)) {
            $this->load();
        }

        $server = Query::query(
            "
            SELECT 
                S.id
            FROM
                tblservers S
            JOIN
                tblservergroupsrel R
                ON S.id = R.serverid 
            WHERE
                R.groupid = :groupID:
                AND disabled = 0
        ",
            [
                ':groupID:' => $this->serverGroupID
            ]
        )->fetchColumn();

        return new \MGModule\RealtimeRegisterSsl\models\whmcs\servers\server($server);
    }

    /**
     * Get Server
     *
     * @return server
     * @author Michal Czech <michael@modulesgarden.com>
     */
    public function getServer()
    {
        if (empty($this->_server)) {
            $this->_server = $this->loadServer();
        }

        return $this->_server;
    }

    /**
     * Load Configuration
     *
     * @param array $params
     * @return \MGModule\RealtimeRegisterSsl\models\product\Configuration
     * @author Michal Czech <michael@modulesgarden.com>
     */
    protected function loadConfiguration($params = [])
    {
        return new Configuration($this->id, $params);
    }

    /**
     * Get Configuration
     *
     * @return configuration
     * @author Michal Czech <michael@modulesgarden.com>
     */
    public function configuration()
    {
        if (empty($this->_configuration)) {
            $this->_configuration = $this->loadConfiguration();
        }

        return $this->_configuration;
    }

    /**
     * Get Custom Fields
     *
     * @return Repository
     * @author Michal Czech <michael@modulesgarden.com>
     */
    public function customFields()
    {
        if (empty($this->_customFields)) {
            $this->_customFields = new Repository('product', $this->id);
        }

        return $this->_customFields;
    }

    public function configOptionsGroups()
    {
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getGid()
    {
        return $this->gid;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getShowDomainOptions()
    {
        return $this->showdomainoptions;
    }

    public function getServerType()
    {
        return $this->serverType;
    }

    public function getServerGroupID()
    {
        return $this->serverGroupID;
    }

    public function getPayType()
    {
        return $this->paytype;
    }

    public function setServerType($name)
    {
        $this->serverType = $name;
    }
}
