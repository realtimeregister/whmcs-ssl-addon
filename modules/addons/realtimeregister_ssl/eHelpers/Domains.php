<?php

namespace AddonModule\RealtimeRegisterSsl\eHelpers;

class Domains {
    public static function validateDomain($domain)
    {
        return preg_match("/([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])+))+$/i", $domain)
            && preg_match("/^.{2,253}$/", $domain)
            && preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain);
    }

    public static function getInvalidDomains(array $domains, $additionalValidation = false) {
        $invalidDomains = [];
        foreach ($domains as $domain) {
            if (!Domains::validateDomain($domain)) {
                $invalidDomains[] = $domain;
            }
        }
        return $invalidDomains;
    }
}
