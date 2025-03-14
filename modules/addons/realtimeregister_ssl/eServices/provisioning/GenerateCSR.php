<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\eHelpers\Domains;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use Exception;

class GenerateCSR
{
    private $params;
    private $post;

    public function __construct(&$params, &$post)
    {
        $this->params = &$params;
        $this->post = &$post;
    }

    public function run()
    {
        try {
            return $this->GenerateCSR();
        } catch (Exception $ex) {
            return json_encode(
                [
                    'success' => 0,
                    'msg' => Lang::getInstance()->T($ex->getMessage()),
                ]
            );
        }
    }

    /**
     * @throws Exception
     */
    private function validateForm()
    {
        if (!Domains::validateDomain($this->post['commonName'])) {
            throw new Exception('invalidCommonName');
        }
        if ($this->post['emailAddress']) {
            if (!filter_var($this->post['emailAddress'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('invalidEmailAddress');
            }
        }
        if ($this->post['countryName']) {
            if (!preg_match("/^[A-Z]{2}$/i", $this->post['countryName'])) {
                throw new Exception('invalidCountryCode');
            }
        }
    }

    /**
     * @throws Exception
     */
    private function GenerateCSR()
    {
        $this->validateForm();

        $dn = [
            'commonName' => $this->post['commonName'],
        ];

        if ($this->post['countryName']) {
            $dn['countryName'] = strtoupper($this->post['countryName']);
        }

        if ($this->post['stateOrProvinceName']) {
            $dn['stateOrProvinceName'] = $this->post['stateOrProvinceName'];
        }

        if ($this->post['localityName']) {
            $dn['localityName'] = $this->post['localityName'];
        }

        if ($this->post['organizationName']) {
            $dn['organizationName'] = $this->post['organizationName'];
        }

        if ($this->post['organizationalUnitName']) {
            $dn['organizationalUnitName'] = $this->post['organizationalUnitName'];
        }

        if ($this->post['emailAddress']) {
            $dn['emailAddress'] = $this->post['emailAddress'];
        }

        $privKey = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($privKey) {
            $serviceid = $this->params['serviceid'];
            if ($serviceid == null) {
                $serviceid = $this->post['serviceID'];
            }

            $saveToDatabase = true;
            if (isset($this->post['doNotSaveToDatabase']) && $this->post['doNotSaveToDatabase']) {
                $saveToDatabase = false;
            }

            openssl_pkey_export($privKey, $pKeyOut);
            if ($saveToDatabase) {
                $this->savePrivateKeyToDatabase($serviceid, $pKeyOut);
            }

            $csr = openssl_csr_new($dn, $privKey);

            if (!$csr) {
                throw new Exception('csrCodeGeneraterFailed');
            }

            openssl_csr_export($csr, $csrOut);
        } else {
            throw new Exception('csrCodeGeneraterFailed');
        }

        return json_encode(
            [
                'success' => 1,
                'msg' => Lang::getInstance()->T('csrCodeGeneratedSuccessfully'),
                'public_key' => $csrOut,
                'private_key' => encrypt($pKeyOut)
            ]
        );
    }

    /**
     * @throws Exception
     */
    public function savePrivateKeyToDatabase($serviceid, $privKey)
    {
        try {
            $sslRepo = new SSL();

            $sslService = $sslRepo->getByServiceId((int)$serviceid);
            if ($sslService) {
                $sslService->setConfigdataKey('private_key', encrypt($privKey));
                $sslService->save();
            }
        } catch (Exception $ex) {
            throw new Exception('csrCodeGeneraterFailed');
        }
    }
}
