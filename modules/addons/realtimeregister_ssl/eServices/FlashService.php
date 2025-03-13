<?php

namespace AddonModule\RealtimeRegisterSsl\eServices;

class FlashService
{
    const STEP_ONE_ERROR = 'realtimeregister_ssl_FLASH_ERROR_STEP_ONE';
    const AUTO_FILL = 'realtimeregister_ssl_FIELDS_AUTO_FILL';

    public static function setStepOneError($message)
    {
        self::set(self::STEP_ONE_ERROR, $message);
    }

    public static function getStepOneError()
    {
        $message = self::getAndUnset(self::STEP_ONE_ERROR);

        if (is_null($message)) {
            return [];
        } else {
            return [
                'errormessage' => '<li>' . $message . '</li>'
            ];
        }
    }

    public static function setFieldsMemory($md5, $fields)
    {
        self::set(self::AUTO_FILL . '_' . $md5, $fields);
    }

    public static function getFieldsMemory($md5, $key = null)
    {
        if (is_null(self::get(self::AUTO_FILL . '_' . $md5))) {
            return [];
        } elseif ($key === null) {
            return self::get(self::AUTO_FILL . '_' . $md5);
        }
        foreach (self::get(self::AUTO_FILL . '_' . $md5) as $field) {
            if($field['name'] === $key){
                return $field['value'];
            }
        }
        return [];
    }

    public static function deleteFieldsMemory($md5) {
        unset($_SESSION[self::AUTO_FILL . '_' . $md5]);
    }

    public static function set($key, $message)
    {
        $_SESSION[$key] = $message;
    }

    public static function getAndUnset($key)
    {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }

    public static function get($key)
    {
        return $_SESSION[$key];
    }

    public static function parseSavedData($client, $domain): array
    {
        $savedData = $domain ? self::getFieldsMemory($domain) : $client?->toArray() ?? [];
        $csrData['firstName'] = $savedData['firstname'] ;
        $csrData['lastName'] = $savedData['lastname'];
        $csrData['phoneNumber'] = $savedData['phonenumber'];
        $csrData['postalCode'] = $savedData['postcode'];
        $csrData['addressLine'] = $savedData['address1'];
        $csrData['country'] = $savedData['country'];
        $csrData['state'] = $savedData['state'];
        $csrData['locality'] = $savedData['city'];
        $csrData['organization'] = $savedData['companyname'] ?? $savedData['orngame'] ?? '';
        $csrData['email'] = $savedData['email'];
        $csrData['privateKey'] = $savedData['privateKey'];

        if($savedData['csr']) {
            $csrData['csr'] = $savedData['csr'];
        } else {
            $csrData['csr'] = "-----BEGIN CERTIFICATE REQUEST-----\n\n-----END CERTIFICATE REQUEST-----";
        }

        $csrData['san'] = $savedData['fields[sans_domains]'];
        $csrData['wildcardSan'] = $savedData['fields[wildcard_san]'];

        return $csrData;
    }
}
