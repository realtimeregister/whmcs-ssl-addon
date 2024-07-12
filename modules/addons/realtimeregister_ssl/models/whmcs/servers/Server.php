<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\servers;

/**
 * Server Model
 * @Table(name=tblservers,preventUpdate,prefixed=false)
 * @author Michal Czech <michael@modulesgarden.com>
 */
class Server extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     * @Column()
     * @var int
     */
    public $id;

    /**
     * @Column()
     * @var string
     */
    public $hostname;

    /**
     * @Column(name=ipaddress)
     * @var string
     */
    public $ip;

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
     * @Column()
     * @var string
     */
    public $accesshash;

    /**
     * @Column()
     * @var string
     */
    public $secure;

    /**
     * @Column(notRequired)
     * @var string
     */
    public $disabled;

    /**
     * Load Server Data
     *
     * @param int $id
     * @param array $data
     * @author Michal Czech <michael@modulesgarden.com>
     */
    public function __construct($id = false, $data = [])
    {
        if ($id !== false && empty($data)) {
            $data = \MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query::select(
                self::fieldDeclaration(),
                self::tableName(),
                [
                    'id' => $id
                ]
            )->fetch();

            if (empty($data)) {
                throw new \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\System('Unable to find Item with ID:' . $id);
            }
        }

        if (isset($data['passwordEncrypted'])) {
            $data['password'] = decrypt($data['passwordEncrypted']);
        }

        if (!empty($data)) {
            $this->fillProperties($data);
        }
    }

    public function save()
    {
        parent::save([
            'password' => encrypt($this->password)
        ]);
    }
}
