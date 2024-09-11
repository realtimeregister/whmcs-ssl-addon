<?php

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\product\configOptions;

use AddonModule\RealtimeRegisterSsl as main;

/**
 * Description of group
 * @Table(name=tblproductconfiggroups,preventUpdate,prefixed=false)
 */
class Group extends \AddonModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    private $_relatedPID = [];
    private $_configOptions = [];

    /**
     * @Column()
     * @var int
     */
    public $id;

    /**
     *
     * @Column()
     * @var string
     */
    public $name;

    /**
     *
     * @Column()
     * @var string
     */
    public $description;

    function addPID($pid)
    {
        $this->_relatedPID[] = $pid;
    }

    function getRelatedPIDs()
    {
        if (empty($this->_relatedPID)) {
            $result = \AddonModule\RealtimeRegisterSsl\mgLibs\MySQL\Query::select(
                [
                    'pid'
                ],
                'tblproductconfiglinks',
                [
                    'gid' => $this->id
                ]);

            while ($row = $result->fetch()) {
                $this->_relatedPID[] = $row['pid'];
            }
        }

        return $this->_relatedPID;
    }

    function save()
    {
        parent::save();

        if ($this->_relatedPID) {
            $result = main\mgLibs\MySQL\Query::select([
                    'pid'
            ],
                'tblproductconfiglinks',
                [
                    'gid' => $this->id
                ]);

            $exists = [];
            while ($row = $result->fetch()) {
                $exists[$row['pid']] = $row['pid'];
            }

            foreach ($this->_relatedPID as $pid) {
                if (!isset($exists[$pid])) {
                    main\mgLibs\MySQL\Query::insert('tblproductconfiglinks', [
                        'pid' => $pid,
                        'gid' => $this->id
                    ]);
                }
            }
        }
    }

    function getConfigOptions()
    {
        if (empty($this->_configOptions)) {
            $this->_configOptions = [];
            $result = main\mgLibs\MySQL\Query::select(
                configOption::fieldDeclaration(),
                configOption::tableName(),
                [
                    'gid' => $this->id
                ]
            );

            while ($row = $result->fetch()) {
                $this->_configOptions[] = new configOption($row['id'], $row);
            }
        }

        return $this->_configOptions;
    }
}
