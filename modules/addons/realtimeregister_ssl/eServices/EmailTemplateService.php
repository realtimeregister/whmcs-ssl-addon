<?php

namespace AddonModule\RealtimeRegisterSsl\eServices;

use AddonModule\RealtimeRegisterSsl\eModels\whmcs\EmailTemplate;
use WHMCS\Database\Capsule;

class EmailTemplateService
{
    public const CONFIGURATION_TEMPLATE_ID = 'RealtimeRegisterSSL - Configuration Required';
    public const EXPIRATION_TEMPLATE_ID = 'RealtimeRegisterSSL - Service Expiration';
    public const SEND_CERTIFICATE_TEMPLATE_ID = 'RealtimeRegisterSSL - Send Certificate';
    public const RENEWAL_TEMPLATE_ID = 'RealtimeRegisterSSL - Renewal';
    public const REISSUE_TEMPLATE_ID = 'RealtimeRegisterSSL - Reissue';
    public const VALIDATION_INFORMATION_TEMPLATE_ID = 'RealtimeRegisterSSL - Validation Information';

    public static function createRenewalTemplate() {
        if(!is_null(self::getTemplate(self::RENEWAL_TEMPLATE_ID))) {
            return 'Template exist, nothing to do here';
        }
        $newTemplate          = new EmailTemplate();
        $newTemplate->type    = 'product';
        $newTemplate->name    = self::RENEWAL_TEMPLATE_ID;
        $newTemplate->subject = 'SSL Certificate - Renew';
        $newTemplate->message = '<p>Dear {$client_name},</p><p>Your current SSL certificate #{$service_id} expires '
            . 'within next 30-days. Please login to the client area and click "Renew" button to start the renewal '
            . 'process.</p><p>{$signature}</p>';
        $newTemplate->attachments  = '';
        $newTemplate->fromname  = '';
        $newTemplate->fromemail  = '';
        $newTemplate->disabled  = '0';
        $newTemplate->custom  = 1;
        $newTemplate->language = '';
        $newTemplate->copyto = '';
        
        $query = Capsule::connection()->select("SHOW COLUMNS FROM `tblemailtemplates` LIKE 'blind_copy_to';");
        if(!empty($query)) {
            $newTemplate->blind_copy_to = '';
        }
        
        $newTemplate->plaintext = '0';
        $newTemplate->created_at = date('Y-m-d H:i:s');
        $newTemplate->updated_at = date('Y-m-d H:i:s');
        $newTemplate->save();
    }

    public static function updateRenewalTemplate()
    {
        $template =  EmailTemplate::whereName(self::RENEWAL_TEMPLATE_ID)->first();
        
        if(empty($template)) {
            self::createRenewalTemplate();
        }
        
        $template          =  EmailTemplate::whereName(self::RENEWAL_TEMPLATE_ID)->first();
        $template->message = '<p>Dear {$client_name},</p><p>Your current SSL certificate #{$service_id} expires '
            . 'within next 30-days. Please login to the client area and click "Renew" button to start the renewal '
            . 'process.</p><p>{$signature}</p>';
        $template->save();
    }

    public static function deleteRenewalTemplate()
    {
        $template = self::getTemplate(self::CONFIGURATION_TEMPLATE_ID);
        if (is_null($template)) {
            return 'Template not exist, nothing to do here';
        }
        $template->delete();
    }
        
    public static function createConfigurationTemplate()
    {
        if (!is_null(self::getTemplate(self::CONFIGURATION_TEMPLATE_ID))) {
            return 'Template exist, nothing to do here';
        }
        $newTemplate          = new EmailTemplate();
        $newTemplate->type    = 'product';
        $newTemplate->name    = self::CONFIGURATION_TEMPLATE_ID;
        $newTemplate->subject = 'SSL Certificate - configuration required';
        $newTemplate->message = '<p>Dear {$client_name},</p><p>Thank you for your order for an SSL Certificate. Before '
            . 'you can use your certificate, it requires configuration which can be done at the URL below.</p><p>'
            . '{$ssl_configuration_link}</p><p>Instructions are provided throughout the process but if you experience '
            . 'any problems or have any questions, please open a ticket for assistance.</p><p>{$signature}</p>';
        $newTemplate->attachments  = '';
        $newTemplate->fromname  = '';
        $newTemplate->fromemail  = '';
        $newTemplate->disabled  = '0';
        $newTemplate->custom  = 1;
        $newTemplate->language = '';
        $newTemplate->copyto = '';
        
        $query = Capsule::connection()->select("SHOW COLUMNS FROM `tblemailtemplates` LIKE 'blind_copy_to';");
        if (!empty($query)) {
            $newTemplate->blind_copy_to = '';
        }
        
        $newTemplate->plaintext = '0';
        $newTemplate->created_at = date('Y-m-d H:i:s');
        $newTemplate->updated_at = date('Y-m-d H:i:s');
        $newTemplate->save();
    }

    public static function updateConfigurationTemplate()
    {
        $template          =  EmailTemplate::whereName(self::CONFIGURATION_TEMPLATE_ID)->first();
        
        if(empty($template)) {
            self::createConfigurationTemplate();
        }
        
        $template          =  EmailTemplate::whereName(self::CONFIGURATION_TEMPLATE_ID)->first();
        $template->message = '<p>Dear {$client_name},</p><p>Thank you for your order for an SSL Certificate'
            . '{if $service_domain} related to domain: {$service_domain}{/if}. Before you can use your certificate, '
            . 'it requires configuration which can be done at the URL below.</p><p>{$ssl_configuration_link}</p>'
            . '<p>Instructions are provided throughout the process but if you experience any problems or have any '
            . 'questions, please open a ticket for assistance.</p><p>{$signature}</p>';
        $template->save();
    }

    public static function deleteConfigurationTemplate()
    {
        $template = self::getTemplate(self::CONFIGURATION_TEMPLATE_ID);
        if (is_null($template)) {
            return 'Template not exist, nothing to do here';
        }
        $template->delete();
    }
    
    public static function createCertificateTemplate()
    {
        if (!is_null(self::getTemplate(self::SEND_CERTIFICATE_TEMPLATE_ID))) {
            return 'Template exist, nothing to do here';
        }
        $newTemplate          = new EmailTemplate();
        $newTemplate->type    = 'product';
        $newTemplate->name    = self::SEND_CERTIFICATE_TEMPLATE_ID;
        $newTemplate->subject = 'SSL Certificate';
        $newTemplate->message = '<p>Dear {$client_name},</p><p>Domain: </p><p>{$domain}</p><p>Intermediate certificate:'
            . ' </p><p>{$ca_bundle}</p><p>CRT: </p><p>{$crt_code}</p><p>{$signature}</p>';
        $newTemplate->attachments  = '';
        $newTemplate->fromname  = '';
        $newTemplate->fromemail  = '';
        $newTemplate->disabled  = '0';
        $newTemplate->custom  = 1;
        $newTemplate->language = '';
        $newTemplate->copyto = '';
        
        $query = Capsule::connection()->select("SHOW COLUMNS FROM `tblemailtemplates` LIKE 'blind_copy_to';");
        if(!empty($query))
        {
            $newTemplate->blind_copy_to = '';
        }
        
        $newTemplate->plaintext = '0';
        $newTemplate->created_at = date('Y-m-d H:i:s');
        $newTemplate->updated_at = date('Y-m-d H:i:s');
        $newTemplate->save();
    }
    
    public static function deleteCertificateTemplate()
    {
        $template = self::getTemplate(self::SEND_CERTIFICATE_TEMPLATE_ID);
        if(is_null($template)) {
            return 'Template not exist, nothing to do here';
        }
        $template->delete();
    }
    
    public static function getTemplate($name)
    {
        return EmailTemplate::whereName($name)->first();
    }
    
    public static function getTemplateName($id)
    {
        $template  = EmailTemplate::whereId($id)->first();
        
        return $template->name;
    }
    
    public static function getGeneralTemplates()
    {
        return EmailTemplate::whereType('product')->get();
    }    
     
    public static function createExpireNotificationTemplate()
    {
        if (!is_null(self::getTemplate(self::EXPIRATION_TEMPLATE_ID))) {
            return 'Template exist, nothing to do here';
        }
        $newTemplate          = new EmailTemplate();
        $newTemplate->type    = 'product';
        $newTemplate->name    = self::EXPIRATION_TEMPLATE_ID;
        $newTemplate->subject = 'Service Expiration Notification - {$service_domain}';
        $newTemplate->message = '<p>Dear {$client_name},</p><p>We would like to inform You about your service <strong>'
            . '#{$service_id}</strong>  is going to expire in {$expireDaysLeft} days.</p><p>{$signature}</p>';
        $newTemplate->attachments  = '';
        $newTemplate->fromname  = '';
        $newTemplate->fromemail  = '';
        $newTemplate->disabled  = '0';
        $newTemplate->custom  = 1;
        $newTemplate->language = '';
        $newTemplate->copyto = '';
        
        $query = Capsule::connection()->select("SHOW COLUMNS FROM `tblemailtemplates` LIKE 'blind_copy_to';");
        if(!empty($query)) {
            $newTemplate->blind_copy_to = '';
        }

        $newTemplate->plaintext = '0';
        $newTemplate->created_at = date('Y-m-d H:i:s');
        $newTemplate->updated_at = date('Y-m-d H:i:s');
        $newTemplate->save();
    }
    
    public static function deleteExpireNotificationTemplate()
    {
        $template = self::getTemplate(self::EXPIRATION_TEMPLATE_ID);
        if (is_null($template)) {
            return 'Template not exist, nothing to do here';
        }
        $template->delete();
    }

    public static function createReissueTemplate()
    {
        if (!is_null(self::getTemplate(self::REISSUE_TEMPLATE_ID))) {
            return 'Template exist, nothing to do here';
        }
        $newTemplate          = new EmailTemplate();
        $newTemplate->type    = 'product';
        $newTemplate->name    = self::REISSUE_TEMPLATE_ID;
        $newTemplate->subject = 'SSL Certificate - Reissue';
        $newTemplate->message = '<p>Dear {$client_name},</p><p>Your current SSL certificate #{$service_id} expires '
            . 'within next 30-days. You are using an SSL subscription and no other payments are required. However, '
            . 'you need to reissue your SSL certificate to receive new files for the next period.</p>'
            . '<p>{$signature}</p>';
        $newTemplate->attachments  = '';
        $newTemplate->fromname  = '';
        $newTemplate->fromemail  = '';
        $newTemplate->disabled  = '0';
        $newTemplate->custom  = 1;
        $newTemplate->language = '';
        $newTemplate->copyto = '';
        
        $query = Capsule::connection()->select("SHOW COLUMNS FROM `tblemailtemplates` LIKE 'blind_copy_to';");
        if (!empty($query)) {
            $newTemplate->blind_copy_to = '';
        }
        
        $newTemplate->plaintext = '0';
        $newTemplate->created_at = date('Y-m-d H:i:s');
        $newTemplate->updated_at = date('Y-m-d H:i:s');
        $newTemplate->save();
    }

    public static function updateReissueTemplate()
    {
        $template          =  EmailTemplate::whereName(self::REISSUE_TEMPLATE_ID)->first();
        
        if (empty($template)) {
            self::createReissueTemplate();
        }
        
        $template          =  EmailTemplate::whereName(self::REISSUE_TEMPLATE_ID)->first();
        $template->subject = 'SSL Certificate - Reissue';
        $template->message = '<p>Dear {$client_name},</p><p>Your current SSL certificate #{$service_id} expires within'
            . ' next 30-days. You are using an SSL subscription and no other payments are required. However, you need '
            . 'to reissue your SSL certificate to receive new files for the next period.</p><p>{$signature}</p>';
        $template->save();
    }
    
    public static function deleteReissueTemplate()
    {
        $template = self::getTemplate(self::REISSUE_TEMPLATE_ID);
        if(is_null($template)) {
            return 'Template not exist, nothing to do here';
        }
        $template->delete();
    }

    public static function createValidationInformationTemplate()
    {
        if (!is_null(self::getTemplate(self::VALIDATION_INFORMATION_TEMPLATE_ID))) {
            return 'Template exist, nothing to do here';
        }
        $newTemplate = new EmailTemplate();
        $newTemplate->type = 'product';
        $newTemplate->name = self::VALIDATION_INFORMATION_TEMPLATE_ID;
        $newTemplate->subject = 'SSL Certificate - Validation Information';
        $newTemplate->message = '<p>Dear {$client_name},</p><p>Your SSL certificate #{$service_id} for {$domain} has been requested</p>' .
            '<p>{$signature}</p>';
        $newTemplate->attachments = '';
        $newTemplate->fromname = '';
        $newTemplate->fromemail = '';
        $newTemplate->disabled = '0';
        $newTemplate->custom = 1;
        $newTemplate->language = '';
        $newTemplate->copyto = '';

        $query = Capsule::connection()->select("SHOW COLUMNS FROM `tblemailtemplates` LIKE 'blind_copy_to';");
        if (!empty($query)) {
            $newTemplate->blind_copy_to = '';
        }

        $newTemplate->plaintext = '0';
        $newTemplate->created_at = date('Y-m-d H:i:s');
        $newTemplate->updated_at = date('Y-m-d H:i:s');
        $newTemplate->save();
    }

    public static function updateValidationInformationTemplate()
    {
        $template = EmailTemplate::whereName(self::VALIDATION_INFORMATION_TEMPLATE_ID)->first();

        if (empty($template)) {
            self::createValidationInformationTemplate();
        }

        $template = EmailTemplate::whereName(self::VALIDATION_INFORMATION_TEMPLATE_ID)->first();
        $template->subject = 'SSL Certificate - Validation Information';
        $template->message = '<p>Dear {$client_name},</p><p>Your SSL certificate #{$service_id} for {$domain} has been requested</p>' .
            '<p>{$signature}</p>';
        $template->save();
    }

    public static function deleteValidationInformationTemplate()
    {
        $template = self::getTemplate(self::VALIDATION_INFORMATION_TEMPLATE_ID);
        if (!is_null($template)) {
            $template->delete();
        }
    }
}
