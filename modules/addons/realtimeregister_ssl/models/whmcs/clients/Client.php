<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\clients;

use MGModule\RealtimeRegisterSsl\mgLibs\models\Orm;
use MGModule\RealtimeRegisterSsl\models\whmcs\clients\customFields\Repository;
use MGModule\RealtimeRegisterSsl\models\whmcs\currencies\Currency;

/**
 * Client Model
 *
 * @Table(name=tblclients,preventUpdate,prefixed=false)
 * @author Michal Czech <michael@modulesgarden.com>
 * @SuppressWarnings(PHPMD)
 */
class Client extends Orm
{
    /**
     *
     * @Column(id)
     * @var int
     */
    public $id;

    /**
     *
     * @Column()
     * @var string
     */
    public $firstname;

    /**
     *
     * @Column()
     * @var string
     */
    public $lastname;

    /**
     *
     * @Column()
     * @var string
     */
    public $email;

    /**
     *
     * @Column()
     * @var string
     */
    public $companyname;

    /**
     *
     * @Column()
     * @var string
     */
    public $status;

    /**
     *
     * @Column(name=currency,as=currencyId)
     * @var string
     */
    protected $currencyId;

    /**
     *
     * @Column(name=address1)
     * @var string
     */
    protected $address1;

    /**
     *
     * @Column(name=address2)
     * @var string
     */
    protected $address2;

    /**
     *
     * @Column(name=city)
     * @var string
     */
    protected $city;

    /**
     *
     * @Column(name=state)
     * @var string
     */
    protected $state;

    /**
     *
     * @Column(name=postcode)
     * @var string
     */
    protected $postcode;

    /**
     *
     * @Column(name=country)
     * @var string
     */
    protected $country;

    /**
     *
     * @Column(name=phonenumber)
     * @var string
     */
    protected $phonenumber;

    /**
     *
     * @Column(name=defaultgateway)
     * @var string
     */
    protected $defaultgateway;

    private $_customFields;

    private $_currency;


    public function getFullName()
    {
        return $this->firstname . ' ' . $this->lastname . (($this->companyname) ? (' (' . $this->companyname . ')') : '');
    }

    public function getProfileUrl()
    {
        return 'clientssummary.php?userid=' . $this->id;
    }

    /**
     * Get Custom Fields
     *
     * @return customFields
     * @author Michal Czech <michael@modulesgarden.com>
     */
    public function customFields()
    {
        if (empty($this->_customFields)) {
            $this->_customFields = new Repository($this->id);
        }

        return $this->_customFields;
    }

    /**
     *
     * @return Currency
     */
    public function getCurrency()
    {
        if (!empty($this->_currency)) {
            return $this->_currency;
        }

        return $this->_currency = new Currency($this->currencyId);
    }

    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    public function getAddress1()
    {
        return $this->address1;
    }

    public function getAddress2()
    {
        return $this->address2;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getPostcode()
    {
        return $this->postcode;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    /**
     *
     * @param int $currencyId
     * @return \MGModule\QuickBooksDesktop\models\whmcs\clients\Client
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
        return $this;
    }

    /**
     *
     * @param string $address1
     * @return \MGModule\QuickBooksDesktop\models\whmcs\clients\Client
     */
    public function setAddress1($address1)
    {
        $this->address1 = $address1;
        return $this;
    }

    /**
     *
     * @param string $address2
     * @return \MGModule\QuickBooksDesktop\models\whmcs\clients\Client
     */
    public function setAddress2($address2)
    {
        $this->address2 = $address2;
        return $this;
    }

    /**
     *
     * @param string $city
     * @return \MGModule\QuickBooksDesktop\models\whmcs\clients\Client
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     *
     * @param string $state
     * @return \MGModule\QuickBooksDesktop\models\whmcs\clients\Client
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }

    /**
     *
     * @param string $postcode
     * @return \MGModule\QuickBooksDesktop\models\whmcs\clients\Client
     */
    public function setPostcode($postcode)
    {
        $this->postcode = $postcode;
        return $this;
    }

    /**
     *
     * @param string $country
     * @return \MGModule\QuickBooksDesktop\models\whmcs\clients\Client
     */
    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }

    /**
     *
     * @param string $phonenumber
     * @return \MGModule\QuickBooksDesktop\models\whmcs\clients\Client
     */
    public function setPhonenumber($phonenumber)
    {
        $this->phonenumber = $phonenumber;
        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getLastname()
    {
        return $this->lastname;
    }

    public function getCompanyName()
    {
        return $this->companyname;
    }

    public function getDefaultGateway()
    {
        return $this->defaultgateway;
    }
}
