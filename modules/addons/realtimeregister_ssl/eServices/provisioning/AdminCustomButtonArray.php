<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

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
        ];

        if ($this->p['status'] !== 'Active') {
            $buttons['Resend Approver Email'] = 'SSLAdminResendApproverEmail';
            $buttons['Reissue Certificate'] = 'SSLAdminReissueCertificate';
            $buttons['Recheck Certificate Details'] = 'SSLAdminRecheckCertificateDetails';
        }

        return $buttons;
    }
}
