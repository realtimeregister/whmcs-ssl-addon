<?php

namespace MGModule\RealtimeRegisterSsl\eServices\provisioning;

class AdminCustomButtonArray
{
    public function run()
    {
        return [
            'Manage SSL'            => 'SSLAdminManageSSL',
            'Resend Approver Email' => 'SSLAdminResendApproverEmail',
            'Resend Certificate'    => 'SSLAdminResendCertificate',
            'Reissue Certificate'   => 'SSLAdminReissueCertificate',
            'View Certificate'      => 'SSLAdminViewCertificate',
            'Recheck Certificate Details' => 'SSLAdminRecheckCertificateDetails' 
        ];
    }
}
