<?php

namespace MGModule\RealtimeRegisterSsl\eHelpers;

class SansDomains
{
    public static function parseDomains($sansDomains)
    {
        $exSansDomains = explode(PHP_EOL, $sansDomains);
        foreach ($exSansDomains as &$sansDomain) {
            $sansDomain = trim($sansDomain);
        }
        foreach ($exSansDomains as $key => &$sansDomain) {
            if (empty($sansDomain)) {
                unset($exSansDomains[$key]);
            }
        }
        return array_unique($exSansDomains);
    }
    
    public static function decodeSanAprroverEmailsAndMethods(&$post)
    {
        if (isset($post['dcvmethod'])) {
            $newDcvMethodArray = [];
            foreach($post['dcvmethod'] as $domain => $method) {
                if (strpos($domain, '___') !== false) {
                    $domain = str_replace('___', '*', $domain);
                }
                $newDcvMethodArray[$domain] = $method;
            }
            
            $post['dcvmethod'] = $newDcvMethodArray;
        }
        if (isset($post['approveremails'])) {
            $newApproverEmailsArray = [];
            foreach($post['approveremails'] as $domain => $method) {
                if (strpos($domain, '___') !== false) {
                    $domain = str_replace('___', '*', $domain);
                }
                $newApproverEmailsArray[$domain] = $method;
            }
            
            $post['approveremails'] = $newApproverEmailsArray;
        }
    }
}
