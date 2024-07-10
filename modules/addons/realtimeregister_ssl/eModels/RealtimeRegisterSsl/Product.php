<?php

namespace MGModule\RealtimeRegisterSsl\eModels\RealtimeRegisterSsl;

use Exception;

class Product
{
    private $webServerMap = [
        'comodo'          => 1,
        'comodo_REALTIMEREGISTERSSL'    => 1,
        'comodo_ukrnames' => 1,
        'comodo_dondca'   => 1,
        'comodo_shino'    => 1,
        'comodo_comssl'   => 1,
        'comodo_ggssl'    => 1,
        'rapidssl'        => 2,
        'thawte'          => 2,
        'symantec'        => 2,
        'geotrust'        => 2,
    ];

    public function getWebServerTypeId()
    {
        if (isset($this->webServerMap[$this->brand])) {
            return $this->webServerMap[$this->brand];
        }
        throw new Exception('Provided brand is not supported.');
    }
    
    public function isOrganizationRequired()
    {
        if ($this->validationType === 'ORGANIZATION_VALIDATION' || $this->validationType === 'EXTENDED_VALIDATION') {
            return true;
        }
        return false;
    }
    
    public function isSanEnabled()
    {
        return $this->includedDomains >= 1;
    }
    
    public function isSanWildcardEnabled()
    {
        return $this->isSanEnabled() && in_array('WILDCARD', $this->features);
    }
    
    public function getPeriods()
    {
        return $this->periods;
    }
    
    public function getMinimalPeriods()
    {
        $periods = $this->getPeriods();
        return reset($periods);
    }
    
    public function getPayType()
    {
        if (strpos(strtolower($this->product), 'trial') === false) {
            return 'recurring';
        }
        return 'free';
    }
}
