<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\addon\admin;

use AddonModule\RealtimeRegisterSsl\addonLibs\forms\CheckboxField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\Creator;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\HiddenField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\LegendField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\SelectField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\TextareaField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\TextField;
use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractController;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use Exception;
use RealtimeRegister\Api\CustomersApi;
use WHMCS\Database\Capsule;

/*
 * API configuration
 */
class ApiConfiguration extends AbstractController
{
    /**
     * This is default page.
     */
    public function indexHTML($input = [], $vars = []): array
    {
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $apiConfigRepo = new Repository();
            $input = (array)$apiConfigRepo->get();
        }

        $form = new Creator('item');

        $field = new HiddenField();
        $field->name = 'tech_phone_country';
        $field->value = $input['tech_phone_country'] ?: 'us';
        $form->addField($field);

        $field = new TextField();
        $field->name = 'api_login';
        $field->value = $input['api_login'];
        $field->error = $this->getFieldError('api_login');
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'api_test';
        $field->options = ['api_test'];
        $field->value = $input['api_test'] ? ['api_test'] : [''];
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = false;
        $field->enableDescription = true;
        $form->addField($field);

        $form->addField('button', 'testConnection', [
            'value' => 'testConnection',
        ]);

        $field = new LegendField();
        $field->name = 'logs_settings_legend';
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'save_activity_logs';
        $field->options = ['save_activity_logs'];
        $field->value = $input['save_activity_logs'] ? ['save_activity_logs'] : [''];
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = false;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new LegendField();
        $field->name = 'recurring_settings';
        $form->addField($field);

        $field = new SelectField();
        $field->name = 'autorenew_ordertype';
        $field->required = true;
        $field->value = $input['autorenew_ordertype'];
        $field->translateOptions = true;
        $field->enableDescription = false;
        $field->options = ['renew_always', 'wait_for_payment'];
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'auto_renew_invoice_recurring';
        $field->options = ['auto_renew_invoice_recurring'];
        $field->value = $input['auto_renew_invoice_recurring'] ? ['auto_renew_invoice_recurring'] : [''];
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = true;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new SelectField();
        $field->name = 'renew_invoice_days_recurring';
        $field->required = true;
        $field->value = $input['renew_invoice_days_recurring'];
        $field->translateOptions = false;
        $field->inline = true;
        $field->colWidth = 2;
        $field->continue = false;
        $field->enableDescription = true;
        $field->options = ['30' => '30', '21' => '21', '14' => '14', '7' => '7', '3' => '3', '1' => '1'];
        $field->error = $this->getFieldError('renew_invoice_days_recurring');
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'send_expiration_notification_recurring';
        $field->options = ['send_expiration_notification_recurring'];
        $field->value = $input['send_expiration_notification_recurring']
            ? ['send_expiration_notification_recurring'] : [''];
        $field->inline = true;
        $field->enableLabel = true;
        $field->colWidth = 5;
        $field->continue = false;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new LegendField();
        $field->name = 'onetime_settings';
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'send_expiration_notification_one_time';
        $field->options = ['send_expiration_notification_one_time'];
        $field->value = $input['send_expiration_notification_one_time']
            ? ['send_expiration_notification_one_time'] : [''];
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = true;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new SelectField();
        $field->disabled = $input['renew_invoice_days_one_time'] ? false : true;
        $field->name = 'renew_invoice_days_one_time';
        $field->required = true;
        $field->value = $input['renew_invoice_days_one_time'];
        $field->translateOptions = false;
        $field->inline = true;
        $field->colWidth = 2;
        $field->continue = true;
        $field->enableDescription = true;
        $field->options = ['30' => '30', '21' => '21', '14' => '14', '7' => '7', '3' => '3', '1' => '1'];
        $field->error = $this->getFieldError('renew_invoice_days_one_time');
        $form->addField($field);

        $field = new LegendField();
        $field->name = 'csr_generator_legend';
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'display_csr_generator';
        $field->options = ['displayCsrGenerator'];
        $field->value = $input['display_csr_generator'] ? ['displayCsrGenerator'] : [''];
        $form->addField($field);
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = true;

        $field = new CheckboxField();
        $field->name = 'auto_install_panel';
        $field->options = ['autoInstallPanel'];
        $field->value = $input['auto_install_panel'] ? ['autoInstallPanel'] : [''];
        $form->addField($field);
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = true;

        $field = new LegendField();
        $field->name = 'send_certificate_template';
        $form->addField($field);

        $field = new TextareaField();
        $field->readonly = false;
        $field->name = 'custom_guide';
        $field->required = false;
        $field->enableDescription = true;
        $field->value = $input['custom_guide'];
        $field->error = $this->getFieldError('custom_guide');
        $form->addField($field);

        $field = new SelectField();
        $field->disabled = false;
        $field->name = 'send_certificate_template';
        $field->required = true;
        $field->value = ($input['send_certificate_template'] == null) ? EmailTemplateService::getTemplate(
            'Realtime Register SSL - Send Certificate'
        )->id : $input['send_certificate_template'];
        $field->translateOptions = false;
        $field->inline = true;
        $field->colWidth = 4;
        $field->continue = false;
        $field->enableDescription = true;
        $field->options = $this->prepareGeneralEmailTemplatedArray(EmailTemplateService::getGeneralTemplates());
        $field->error = $this->getFieldError('send_certificate_template');
        $form->addField($field);

        $field = new LegendField();
        $field->name = 'client_area_summary_orders';
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'display_ca_summary';
        $field->options = ['displayCASummary'];
        $field->value = $input['display_ca_summary'] ? ['displayCASummary'] : [''];
        $field->inline = false;
        $field->colWidth = 2;
        $field->continue = false;
        $field->enableDescription = false;
        $form->addField($field);

        $field = new TextField();
        $field->readonly = false;
        $field->name = 'sidebar_templates';
        $field->required = false;
        $field->enableDescription = true;
        $field->value = $input['sidebar_templates'];
        $field->error = $this->getFieldError('sidebar_templates');
        $form->addField($field);

        $field = new SelectField();
        $field->disabled = false;
        $field->name = 'summary_expires_soon_days';
        $field->required = true;
        $field->value = $input['summary_expires_soon_days'];
        $field->translateOptions = false;
        $field->inline = false;
        $field->colWidth = 3;
        $field->continue = false;
        $field->enableDescription = true;
        $field->options = ['30' => '30', '15' => '15', '10' => '10'];
        $field->error = $this->getFieldError('summary_expires_soon_days');
        $form->addField($field);

        $form->addField('submit', 'addon-action', [
            'value' => 'saveItem'
        ]);

        $vars['form'] = $form->getHTML();

        return [
            'tpl' => 'api_configuration',
            'vars' => $vars
        ];
    }

    public function saveItemHTML($input, $vars = [])
    {
        $apiConfigRepo = new Repository();
        $crons = (array)$apiConfigRepo->get();
        if ($this->checkToken()) {
            try {
                $checkFieldsArray = [
                    'display_csr_generator',
                    'profile_data_csr',
                    'auto_install_panel',
                    'auto_renew_invoice_recurring',
                    'send_expiration_notification_recurring',
                    'send_expiration_notification_one_time',
                    'renewal_invoice_status_unpaid',
                    'display_ca_summary',
                    'disable_email_validation',
                    'api_test',
                    'visible_renew_button',
                    'save_activity_logs'
                ];
                foreach ($checkFieldsArray as $field) {
                    if (isset($input[$field])) {
                        $input[$field] = true;
                    } else {
                        $input[$field] = false;
                    }
                }
                if (!$input['auto_renew_invoice_recurring']) {
                    $input['renew_invoice_days_recurring'] = null;
                }
                if (!$input['display_csr_generator']) {
                    $input['default_csr_generator_country'] = null;
                }

                $apiConfigRepo = new Repository();
                $apiConfigRepo->setConfiguration(array_merge($crons, $input));
            } catch (Exception $ex) {
                $vars['formError'] = Lang::T('messages', $ex->getMessage());
            }
        }

        $vars['success'] = Lang::absoluteT('changessavedsuccessfully');
        return $this->indexHTML($input, $vars);
    }

    public function testConnectionJSON($input = [], $vars = [])
    {
        ApiProvider::standalone(CustomersApi::class, $input['api_login'], $input['api_test'] === 'true')
            ->priceList(ApiProvider::parseCustomer($input['api_login']));
        return [
            'success' => Lang::T('messages', 'api_connection_success')
        ];
    }

    private function prepareGeneralEmailTemplatedArray($templates)
    {
        $templatesArray = [];

        foreach ($templates as $template) {
            $templatesArray[$template->id] = $template->name;
        }

        return $templatesArray;
    }
}
