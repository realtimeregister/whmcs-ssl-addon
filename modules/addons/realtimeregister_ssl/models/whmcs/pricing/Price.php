<?php

/* * ********************************************************************
 *
 *
 * This software is furnished under a license and may be used and copied
 * only  in  accordance  with  the  terms  of such  license and with the
 * inclusion of the above copyright notice.  This software  or any other
 * copies thereof may not be provided or otherwise made available to any
 * other person.  No title to and  ownership of the  software is  hereby
 * transferred.
 *
 *
 * ******************************************************************** */

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\pricing;

use AddonModule\RealtimeRegisterSsl as main;
use AddonModule\RealtimeRegisterSsl\models\whmcs\currencies\Currency;

/**
 * Description of Pricing
 *
 * @Table(name=tblpricing,preventUpdate,prefixed=false)
 * @SuppressWarnings(PHPMD)
 */
class Price extends \AddonModule\RealtimeRegisterSsl\addonLibs\models\Orm
{
    /**
     * @Column()
     * @var int
     */
    protected $id;

    /**
     * @Column(name=type)
     * @var string
     */
    protected $type;

    /**
     * @Column(name=currency,as=currencyId)
     * @var int
     */
    protected $currencyId;

    /**
     * @Column(name=relid,as=relationId)
     * @var int
     */
    protected $relationId;

    /**
     * For domain 1 Year
     * @Column(name=msetupfee,as=monthlySetupFee)
     * @var int
     */
    protected $monthlySetupFee;

    /**
     * For domain 2 Years
     * @Column(name=qsetupfee,as=quarterlySetupFee)
     * @var int
     */
    protected $quarterlySetupFee;

    /**
     *  For domain 3 Years
     * @Column(name=ssetupfee,as=semiAnnuallySetupFee)
     * @var int
     */
    protected $semiAnnuallySetupFee;

    /**
     * For domain 4 Years
     * @Column(name=asetupfee,as=annuallySetupFee)
     * @var int
     */
    protected $annuallySetupFee;

    /**
     * For domain 5 Years
     * @Column(name=bsetupfee,as=bienniallySetupFee)
     * @var int
     */
    protected $bienniallySetupFee;

    /**
     * @Column(name=tsetupfee,as=trienniallySetupFee)
     * @var int
     */
    protected $trienniallySetupFee;

    /**
     * For domain 6 Years
     * @Column(name=monthly)
     * @var int
     */
    protected $monthly;

    /**
     * For domain 7 Years
     * @Column(name=quarterly)
     * @var int
     */
    protected $quarterly;

    /**
     * For domain 8 Years
     * @Column(name=semiannually,as=semiAnnually)
     * @var int
     */
    protected $semiAnnually;

    /**
     * For domain 9 Years
     * @Column(name=annually)
     * @var int
     */
    protected $annually;

    /**
     * For domain 10 Years
     * @Column(name=biennially)
     * @var int
     */
    protected $biennially;

    /**
     * @Column(name=triennially)
     * @var int
     */
    protected $triennially;

    private $_currency;

    public function getId()
    {
        return $this->id;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    public function getRelationId()
    {
        return $this->relationId;
    }

    public function getMonthlySetupFee()
    {
        return $this->monthlySetupFee;
    }

    public function getQuarterlySetupFee()
    {
        return $this->quarterlySetupFee;
    }

    public function getSemiAnnuallySetupFee()
    {
        return $this->semiAnnuallySetupFee;
    }

    public function getAnnuallySetupFee()
    {
        return $this->annuallySetupFee;
    }

    public function getBienniallySetupFee()
    {
        return $this->bienniallySetupFee;
    }

    public function getTrienniallySetupFee()
    {
        return $this->trienniallySetupFee;
    }

    public function getMonthly()
    {
        return $this->monthly;
    }

    public function getQuarterly()
    {
        return $this->quarterly;
    }

    public function getSemiAnnually()
    {
        return $this->semiAnnually;
    }

    public function getAnnually()
    {
        return $this->annually;
    }

    public function getBiennially()
    {
        return $this->biennially;
    }

    public function getTriennially()
    {
        return $this->triennially;
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
        return $this->_currency = new Currency($this->getCurrencyId());
    }

    public function getPrice($billingCycle)
    {
        switch ($billingCycle) {
            case BillingCycle::FREE:
                return 0;
            case BillingCycle::ONE_TIME:
            case BillingCycle::MONTHLY:
                return $this->getMonthly();
            case BillingCycle::QUARTERLY:
                return $this->getQuarterly();
            case BillingCycle::SEMI_ANNUALLY:
                return $this->getSemiAnnually();
            case BillingCycle::ANNUALLY:
                return $this->getAnnually();
            case BillingCycle::BIENNIALLY:
                return $this->getBiennially();
            case BillingCycle::TRIENNIALLY:
                return $this->getTriennially();
            //Domains
            case BillingCycle::YEAR:
                return $this->getMonthlySetupFee();
            case BillingCycle::YEARS_2:
                return $this->getQuarterlySetupFee();
            case BillingCycle::YEARS_3:
                return $this->getSemiAnnuallySetupFee();
            case BillingCycle::YEARS_4:
                return $this->getAnnuallySetupFee();
            case BillingCycle::YEARS_5:
                return $this->getBienniallySetupFee();
            case BillingCycle::YEARS_6:
                return $this->getMonthly();
            case BillingCycle::YEARS_7:
                return $this->getQuarterly();
            case BillingCycle::YEARS_8:
                return $this->getSemiAnnually();
            case BillingCycle::YEARS_9:
                return $this->getAnnually();
            case BillingCycle::YEARS_10:
                return $this->getBiennially();
            default:
                throw new main\addonLibs\exceptions\System('Invalid billing cycle: ' . $billingCycle);
        }
    }
}
