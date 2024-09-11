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

namespace AddonModule\RealtimeRegisterSsl\models\whmcs\domains;

/**
 * Description of DomainPrice
 *
 * @Table(name=tbldomainpricing,preventUpdate,prefixed=false)
 */
class DomainTld extends \AddonModule\RealtimeRegisterSsl\mgLibs\models\Orm
{
    /**
     * @Column()
     * @var int
     */
    protected $id;

    /**
     * @Column(name=extension)
     * @var string
     */
    protected $extension;

    /**
     * @Column(name=dnsmanagement,as=dnsManagement)
     * @var string
     */
    protected $dnsManagement;

    /**
     * @Column(name=emailforwarding,as=emailForwarding)
     * @var string
     */
    protected $emailForwarding;

    /**
     * @Column(name=idprotection,as=idProtection)
     * @var string
     */
    protected $idProtection;

    /**
     * @Column(name=eppcode,as=eppCode)
     * @var string
     */
    protected $eppCode;

    /**
     * @Column(name=autoreg,as=autoreg)
     * @var string
     */
    protected $autoreg;

    private $_domainRegisterPricing;

    public function getId()
    {
        return $this->id;
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function getDnsManagement()
    {
        return $this->dnsManagement;
    }

    public function getEmailForwarding()
    {
        return $this->emailForwarding;
    }

    public function getIdProtection()
    {
        return $this->idProtection;
    }

    public function getEppCode()
    {
        return $this->eppCode;
    }

    public function getAutoreg()
    {
        return $this->autoreg;
    }

    /**
     * Get Pricing
     * @return \AddonModule\RealtimeRegisterSsl\models\whmcs\pricing\Price[]
     */
    public function getDomainRegisterPricing()
    {
        if (!empty($this->_domainRegisterPricing)) {
            return $this->_domainRegisterPricing;
        }

        $repositor = new \AddonModule\RealtimeRegisterSsl\models\whmcs\pricing\Repository();
        $repositor->onlyDomainRegister();
        $repositor->withRelation($this->id);
        $repositor->withDomainCycle();
        $this->_domainRegisterPricing = [];
        foreach ($repositor->get() as $price) {
            $this->_domainRegisterPricing[] = $price;
        }
        unset($repositor);
        return $this->_domainRegisterPricing;
    }


    public function getPrice($currencyId, $billingCycle)
    {
        $repositor = new \AddonModule\RealtimeRegisterSsl\models\whmcs\pricing\Repository();
        $repositor->onlyDomainRegister();
        $repositor->withRelation($this->id);
        $repositor->withDomainCycle();
        $repositor->onlyCurrency($currencyId);
        foreach ($repositor->get() as $price) {
            return $price->getPrice($billingCycle);
        }
        return null;
    }
}
