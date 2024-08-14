<?php

namespace MGModule\RealtimeRegisterSsl\models\testGroup\TestItem;

use MGModule\RealtimeRegisterSsl\mgLibs;
use MGModule\RealtimeRegisterSsl\models\testGroup\simpleItem\simpleItem;

/**
 * Example Item Class
 *
 * @Table(name=test_item)
 * @author Michal Czech <michael@modulesgarden.com>
 */
class TestItem extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    static $avaibleOptionsA = [
        1 => 'Option1'
        ,
        2 => 'Option2'
        ,
        3 => 'Option3'
    ];

    static $avaibleOptionsB = [1, 2, 3, 4];
    static $avaibleOptionsC = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    /**
     * ID field
     *
     * @Column(id)
     * @var int
     */
    public $id;

    /**
     * Text Field
     *
     * @Column(varchar=512,name=text_field)
     * @Validation(notEmpty)
     * @var string
     */
    public $textField;

    /**
     * Text2 Field
     *
     * @Column(varchar=256)
     * @Validation(notEmpty)
     * @var string
     */
    public $text2;

    /**
     * Checkbox Value
     *
     * @Column(text,as=checkboxSerialized)
     * @var array
     */
    public $checkbox;

    /**
     * Boolean Field
     *
     * @Column(boolean)
     * @var boolean
     */
    public $onoff;

    /**
     * Value
     *
     * @Column(smallint)
     * @var int
     */
    public $radio;

    /**
     * @Column(int)
     * @var int
     */
    public $option;

    /**
     *  Number of relations
     *
     * @var int
     */
    public $simpleNum;

    /**
     * Multi Value
     *
     * @Column(text,as=option2Serialized)
     * @Validation(notEmpty)
     * @var array
     */
    public $option2;

    /**
     * Password
     *
     * @Column(varchar=32,as=passwordEncrypted)
     * @var string
     */
    public $password;

    function __construct($id = false, $data = [])
    {
        if ($id !== false && empty($data)) {
            $sql = "
                SELECT
                    " . mgLibs\MySQL\Query::formatSelectFields(testItem::fieldDeclaration(), 'B') . "
                    ,count(S." . SimpleItem::getProperyColumn('id') . ") as simpleNum
                FROM
                    " . testItem::tableName() . " B
                JOIN
                    " . SimpleItem::tableName() . " S
                WHERE
                    B.id = :id:
                GROUP BY 
                    B.id
            ";

            $data = \MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query::query($sql, ['id' => $id])->fetch();

            if (empty($data)) {
                throw new \MGModule\RealtimeRegisterSsl\mgLibs\exceptions\System('Unable to find Element with ID:' . $id);
            }
        }

        if ($data) {
            $data['checkbox'] = unserialize(base64_decode($data['checkboxSerialized']));
            $data['option2'] = unserialize(base64_decode($data['option2Serialized']));
            $data['password'] = $this->decrypt($data['passwordEncrypted']);
        }

        parent::__construct(false, $data);
    }

    function save($data = [])
    {
        $data = [
            'text_field' => $this->textField,
            'text2' => $this->text2,
            'checkbox' => base64_encode(serialize($this->checkbox)),
            'onoff' => (bool)$this->onoff,
            'radio' => $this->radio,
            'option' => $this->option,
            'option2' => base64_encode(serialize($this->option2)),
            'password' => $this->encrypt($this->password)
        ];

        if ($this->id) {
            \MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query::update(
                self::tableName(),
                $data,
                [
                    'id' => $this->id
                ]
            );
        } else {
            $this->id = \MGModule\RealtimeRegisterSsl\mgLibs\MySQL\Query::insert(self::tableName(), $data);
        }
    }
}
