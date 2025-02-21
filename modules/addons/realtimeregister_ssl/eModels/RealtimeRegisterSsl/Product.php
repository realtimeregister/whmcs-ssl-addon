<?php

namespace AddonModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl;

class Product
{
    public function isOrganizationRequired()

    {
        if ($this->validationType === 'ORGANIZATION_VALIDATION' || $this->validationType === 'EXTENDED_VALIDATION') {
            return true;
        }
        return false;
    }
    
    public function isSanEnabled()
    {
        return $this->includedDomains > 1;
    }
    
    public function isSanWildcardEnabled()
    {
        return $this->isSanEnabled() && in_array('WILDCARD', $this->features);
    }
    
    public function getPeriods()
    {
        return $this->periods;
    }

    public function getMaxDomains()
    {
        return $this->maxDomains;
    }

    public function getIncludedDomains()
    {
        return $this->includedDomains;
    }
    
    public function getMinimalPeriods()
    {
        $periods = $this->getPeriods();
        return reset($periods);
    }
    
    public function getPayType()
    {
        if (!str_contains(strtolower($this->product), 'trial')) {
            return 'recurring';
        }
        return 'free';
    }

    public function getMaxPeriod() {
        return max($this->periods);
    }
}
