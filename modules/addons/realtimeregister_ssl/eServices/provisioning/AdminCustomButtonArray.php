<?php

namespace AddonModule\RealtimeRegisterSsl\eServices\provisioning;

class AdminCustomButtonArray
{
    private array $p;

    public function __construct(array $params)
    {
        $this->p = &$params;
    }

    public function run(): array
    {
        $buttons = [
            'Manage SSL'            => 'SSLAdminManageSSL',
            'Resend Certificate'    => 'SSLAdminResendCertificate',
            'View Certificate'      => 'SSLAdminViewCertificate',
            'Reissue Certificate'   => 'SSLAdminReissueCertificate'
        ];

        if (strtolower($this->p['status']) !== 'active') {
            $buttons['Resend Approver Email'] = 'SSLAdminResendApproverEmail';
            $buttons['Recheck Certificate Details'] = 'SSLAdminRecheckCertificateDetails';
        }

        return $buttons;
    }
}
