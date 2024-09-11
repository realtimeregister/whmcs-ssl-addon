<?php

namespace MGModule\RealtimeRegisterSsl\models\testGroup\simpleItem;

use MGModule\RealtimeRegisterSsl\models\testGroup\testItem\TestItem;

/**
 * Example Item Class
 * 
 * @Table(name=simple_item)
 */
class SimpleItem extends \MGModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     * ID field
     * 
     * @Column(id)
     * @var int 
     */
    public $id;
    
    /**
     *
     * @Column(varchar)
     * @var string
     */
    public $name;
    
    /**
     *
     * @var TestItem
     */
    private $_testItem;
    
    /**
     *
     * @Column(int,refrence=models\testGroup\testItem\TestItem::id)
     * @var int 
     */
    public $testItemID;
    
    public function gettestItem()
    {
        if (empty($this->_testItem)) {
            $this->_testItem = new TestItem($this->testItemID);
        }
        
        return $this->_testItem;
    }
    
    public function settestItem(TestItem $item)
    {
        $this->_testItem = $item;
        $this->testItemID = $item->id;
    }
}
