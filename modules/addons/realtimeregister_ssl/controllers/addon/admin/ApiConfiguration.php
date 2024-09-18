<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\addon\admin;

use AddonModule\RealtimeRegisterSsl as main;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\CheckboxField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\Creator;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\HiddenField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\LegendField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\SelectField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\TextareaField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\TextField;
use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractController;
use AddonModule\RealtimeRegisterSsl\eHelpers\Migration;
use AddonModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\config\Countries;
use AddonModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL;
use AddonModule\RealtimeRegisterSsl\eServices\EmailTemplateService;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;
use AddonModule\RealtimeRegisterSsl\models\whmcs\product\Products;
use Exception;
use SandwaveIo\RealtimeRegister\Api\CustomersApi;
use SandwaveIo\RealtimeRegister\Domain\PriceCollection;
use WHMCS\Database\Capsule;

/*
 * Base example
 */
class ApiConfiguration extends AbstractController
{
    /**
     * This is default page.
     */
    public function indexHTML($input = [], $vars = []): array
    {
        $oldModuleProducts = $oldModuleServices = [];
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $apiConfigRepo = new Repository();
            $input = (array)$apiConfigRepo->get();

            $productsRepo = new Products();
            $productsRepo->onlyModule(Migration::MODULE_NAME);

            foreach ($productsRepo->get() as $product) {
                $oldModuleProducts[] = '<a target="_blank" href="configproducts.php?action=edit&id=' .
                    $product->id . '">#' . $product->id . '</a>';
            }
            $SSLOrders = new SSL();
            $orders = $SSLOrders->getBy(['module' => Migration::MODULE_NAME]);

            foreach ($orders as $ssl) {
                $oldModuleServices[] = '<a target="_blank" href="clientsservices.php?id='
                    . $ssl->serviceid . '">#' . $ssl->serviceid . '</a>';
            }
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
        $field->name = 'renewal_settings_legend';
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'visible_renew_button';
        $field->options = ['visible_renew_button'];
        $field->value = $input['visible_renew_button'] ? ['visible_renew_button'] : [''];
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = false;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'auto_renew_invoice_reccuring';
        $field->options = ['auto_renew_invoice_reccuring'];
        $field->value = $input['auto_renew_invoice_reccuring'] ? ['auto_renew_invoice_reccuring'] : [''];
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = true;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new SelectField();
        $field->disabled = $input['auto_renew_invoice_reccuring'] ? false : true;
        $field->name = 'renew_invoice_days_reccuring';
        $field->required = true;
        $field->value = $input['renew_invoice_days_reccuring'];
        $field->translateOptions = false;
        $field->inline = true;
        $field->colWidth = 2;
        $field->continue = false;
        $field->enableDescription = true;
        $field->options = ['30' => '30', '21' => '21', '14' => '14', '7' => '7', '3' => '3', '1' => '1', '0' => '0'];
        $field->error = $this->getFieldError('renew_invoice_days_reccuring');
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'send_expiration_notification_reccuring';
        $field->options = ['send_expiration_notification_reccuring'];
        $field->value = $input['send_expiration_notification_reccuring']
            ? ['send_expiration_notification_reccuring'] : [''];
        $field->inline = true;
        $field->enableLabel = true;
        $field->colWidth = 5;
        $field->continue = false;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'auto_renew_invoice_one_time';
        $field->options = ['auto_renew_invoice_one_time'];
        $field->value = $input['auto_renew_invoice_one_time'] ? ['auto_renew_invoice_one_time'] : [''];
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
        $field->continue = false;
        $field->enableDescription = true;
        $field->options = ['30' => '30', '21' => '21', '14' => '14', '7' => '7', '3' => '3', '1' => '1', '0' => '0'];
        $field->error = $this->getFieldError('renew_invoice_days_one_time');
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'send_expiration_notification_one_time';
        $field->options = ['send_expiration_notification_one_time'];
        $field->value = $input['send_expiration_notification_one_time']
            ? ['send_expiration_notification_one_time'] : [''];
        $field->inline = true;
        $field->colWidth = 5;
        $field->continue = false;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'automatic_processing_of_renewal_orders';
        $field->options = ['automatic_processing_of_renewal_orders'];
        $field->value = $input['automatic_processing_of_renewal_orders']
            ? ['automatic_processing_of_renewal_orders'] : [''];
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = true;
        $field->enableDescription = true;
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'renewal_invoice_status_unpaid';
        $field->options = ['renewal_invoice_status_unpaid'];
        $field->value = $input['renewal_invoice_status_unpaid'] ? ['renewal_invoice_status_unpaid'] : [''];
        $field->inline = true;
        $field->colWidth = 3;
        $field->continue = true;
        $field->enableDescription = true;
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

        $field = new SelectField();
        $field->disabled = $input['display_csr_generator'] ? false : true;
        $field->name = 'default_csr_generator_country';
        $field->required = true;
        $field->value = $input['default_csr_generator_country'];
        $field->translateOptions = false;
        $field->inline = true;
        $field->colWidth = 5;
        $field->continue = false;
        $field->enableDescription = true;
        $field->options = Countries::getInstance()->getCountriesForAddonDropdown();
        $field->error = $this->getFieldError('default_csr_generator_country');
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'profile_data_csr';
        $field->options = ['profileDataCsr'];
        $field->value = $input['profile_data_csr'] ? ['profileDataCsr'] : [''];
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
        $field->colWidth = 5;
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

        $field = new LegendField();
        $field->name = 'tech_legend';
        $form->addField($field);

        $field = new CheckboxField();
        $field->name = 'use_admin_contact';
        $field->options = ['useAdministrative'];
        $field->value = $input['use_admin_contact'] ? ['useAdministrative'] : [''];
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_firstname';
        $field->required = true;
        $field->options = ['1', '2'];
        $field->value = $input['tech_firstname'];
        $field->error = $this->getFieldError('tech_firstname');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_lastname';
        $field->required = true;
        $field->value = $input['tech_lastname'];
        $field->error = $this->getFieldError('tech_lastname');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_organization';
        $field->required = true;
        $field->value = $input['tech_organization'];
        $field->error = $this->getFieldError('tech_organization');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_addressline1';
        $field->required = true;
        $field->value = $input['tech_addressline1'];
        $field->error = $this->getFieldError('tech_addressline1');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_phone';
        $field->required = true;
        $field->value = $input['tech_phone'];
        $field->error = $this->getFieldError('tech_phone');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_title';
        $field->required = true;
        $field->value = $input['tech_title'];
        $field->error = $this->getFieldError('tech_title');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_email';
        $field->required = true;
        $field->value = $input['tech_email'];
        $field->error = $this->getFieldError('tech_email');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_city';
        $field->required = true;
        $field->value = $input['tech_city'];
        $field->error = $this->getFieldError('tech_city');
        $form->addField($field);

        $field = new SelectField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_country';
        $field->required = true;
        $field->value = $input['tech_country'];
        $field->translateOptions = false;
        $field->options = Countries::getInstance()->getCountriesForAddonDropdown();
        $field->error = $this->getFieldError('tech_country');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_postalcode';
        $field->required = true;
        $field->value = $input['tech_postalcode'];
        $field->error = $this->getFieldError('tech_postalcode');
        $form->addField($field);

        $field = new TextField();
        $field->readonly = $input['use_admin_contact'] ? true : false;
        $field->name = 'tech_region';
        $field->required = true;
        $field->value = $input['tech_region'];
        $field->error = $this->getFieldError('tech_region');
        $form->addField($field);

        $form->addField('submit', 'addon-action', [
            'value' => 'saveItem'
        ]);

        $vars['form'] = $form->getHTML();

        //get cron command line
        $vars['cronCommandLine'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'cron.php';
        $vars['cronCommandLine7'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'APIProcessingOrders.php';
        $vars['cronCommandLine8'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'APIDailyStatusUpdater.php';
        $vars['cronCommandLine2'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'certificateStatsLoader.php';
        $vars['cronCommandLine3'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'notifier.php';
        $vars['cronCommandLine4'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'certificateSender.php';
        $vars['cronCommandLine5'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'APIPriceUpdater.php';
        $vars['cronCommandLine6'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'certificateDetailsUpdater.php';
        $vars['cronCommandLine9'] = 'php -q ' . ROOTDIR . DS . 'modules' . DS . 'addons'
            . DS . 'realtimeregister_ssl' . DS . 'cron' . DS . 'installCertificates.php';

        return [
            'tpl' => 'api_configuration',
            'vars' => $vars
        ];
    }

    public function saveItemHTML($input, $vars = [])
    {
        if ($this->checkToken()) {
            try {
                $checkFieldsArray = [
                    'use_admin_contact',
                    'display_csr_generator',
                    'profile_data_csr',
                    'auto_install_panel',
                    'auto_renew_invoice_one_time',
                    'auto_renew_invoice_reccuring',
                    'send_expiration_notification_reccuring',
                    'send_expiration_notification_one_time',
                    'automatic_processing_of_renewal_orders',
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
                if (!$input['auto_renew_invoice_reccuring']) {
                    $input['renew_invoice_days_reccuring'] = null;
                }
                if (!$input['auto_renew_invoice_one_time']) {
                    $input['renew_invoice_days_one_time'] = null;
                }
                if (!$input['display_csr_generator']) {
                    $input['default_csr_generator_country'] = null;
                }

                $apiConfigRepo = new Repository();
                $apiConfigRepo->setConfiguration($input);
            } catch (Exception $ex) {
                $vars['formError'] = Lang::T('messages', $ex->getMessage());
            }
        }

        return $this->indexHTML($input, $vars);
    }

    public function testConnectionJSON($input = [], $vars = [])
    {
        $customersApi = ApiProvider::getInstance()->getApi(CustomersApi::class);
        /** @var PriceCollection $apiProducts */
        $customersApi->priceList(ApiProvider::getCustomer());
        return [
            'success' => Lang::T('messages', 'api_connection_success')
        ];
    }

    public function runMigrationJSON($input = [], $vars = [])
    {
        try {
            Migration::getInstance()->run();
        } catch (Exception $ex) {
            return [
                'success' => false,
                'error' => $ex->getMessage()
            ];
        }

        return [
            'success' => Lang::T('messages', 'data_migration_success')
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

    /**
     * This is custom page.
     * @param type $input
     * @param type $vars
     * @return array
     */
    public function pageHTML()
    {
        $vars = [];

        return
            [
                //You have to create tpl file  /modules/addons/RealtimeRegisterSsl/templates/admin/pages/example1/page.1tpl
                'tpl' => 'page',
                'vars' => $vars
            ];
    }
    /*
     * ************************************************************************
     * AJAX USING ARRAY
     * ************************************************************************
     */

    /**
     * Display custom page for ajax errors
     * @param type $input
     * @param type $vars
     * @return array
     */
    public function ajaxErrorHTML()
    {
        return
            [
                'tpl' => 'ajaxError'
            ];
    }

    /**
     * Return error message using array
     * @param type $input
     * @param type $vars
     * @return array
     */
    public function getErrorArrayJSON()
    {
        return
            [
                'error' => 'Custom error'
            ];
    }

    /**
     * Return success message using array
     * @param type $input
     * @param type $vars
     * @return array
     */
    public function getSuccessArrayJSON()
    {
        return
            [
                'success' => 'Custom success'
            ];
    }

    /*
     * ************************************************************************
     * AJAX USING DATA-ACT
     * ***********************************************************************
     */

    public function ajaxErrorDataActHTML()
    {
        return
            [
                'tpl' => 'ajaxErrorDataAct'
            ];
    }

    /*
     * ************************************************************************
     * AJAX CONTENT
     * ***********************************************************************
     */

    public function ajaxContentHTML()
    {
        return
            [
                'tpl' => 'ajaxContent'
            ];
    }

    public function ajaxContentJSON()
    {
        return
            [
                'html' => main\addonLibs\Smarty::I()->view('ajaxContentJSON')
            ];
    }

    /*     * ******************************************************
     * CREATOR
     * ***************************************************** */

    public function getCreatorJSON()
    {
        $creator = new main\addonLibs\forms\Popup('mymodal');
        $creator->addField(
            new TextField([
                'name' => 'customTextField',
                'value' => 'empty_value',
                'placeholder' => 'placeholder!'
            ])
        );

        return [
            'modal' => $creator->getHTML()
        ];
    }
}
