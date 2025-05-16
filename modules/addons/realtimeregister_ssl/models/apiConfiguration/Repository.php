<?php

namespace AddonModule\RealtimeRegisterSsl\models\apiConfiguration;

use Illuminate\Database\Capsule\Manager as Capsule;

class Repository extends \AddonModule\RealtimeRegisterSsl\addonLibs\models\Repository
{
    public $tableName = 'REALTIMEREGISTERSSL_api_configuration';

    public function getModelClass()
    {
        return __NAMESPACE__ . '\ApiConfigurationItem';
    }

    public function get()
    {
        return Capsule::table($this->tableName)->first();
    }

    public function setConfiguration($params)
    {
        if (empty($params['tech_fax'])) {
            $params['tech_fax'] = '';
        }

        if (is_null($this->get())) {

            Capsule::table($this->tableName)->insert(
                [
                    'api_login' => $params['api_login'],
                    'api_test' => $params['api_test'],
                    'rate' => $params['rate'],
                    'display_csr_generator' => $params['display_csr_generator'],
                    'auto_install_panel' => $params['auto_install_panel'],
                    'auto_renew_invoice_recurring' => $params['auto_renew_invoice_recurring'],
                    'send_expiration_notification_recurring' => $params['send_expiration_notification_recurring'],
                    'send_expiration_notification_one_time' => $params['send_expiration_notification_one_time'],
                    'renewal_invoice_status_unpaid' => $params['renewal_invoice_status_unpaid'],
                    'visible_renew_button' => $params['visible_renew_button'],
                    'save_activity_logs' => $params['save_activity_logs'],
                    'renew_invoice_days_recurring' => $params['renew_invoice_days_recurring'],
                    'renew_invoice_days_one_time' => $params['renew_invoice_days_one_time'],
                    'summary_expires_soon_days' => $params['summary_expires_soon_days'],
                    'send_certificate_template' => $params['send_certificate_template'],
                    'display_ca_summary' => $params['display_ca_summary'],
                    'sidebar_templates' => $params['sidebar_templates'],
                    'custom_guide' => $params['custom_guide'],
                    'disable_email_validation' => $params['disable_email_validation'],
                    'autorenew_ordertype' => $params['autorenew_ordertype'],
                    'cron_daily' =>  1,
                    'cron_processing' =>  1,
                    'cron_synchronization' =>  1,
                    'cron_ssl_summary_stats' =>  1,
                    'cron_renewal' =>  1,
                    'cron_send_certificate' =>  1,
                    'cron_price_updater' =>  1,
                    'cron_certificate_details_updater' =>  1,
                    'cron_certificate_installer' => 1,
                ]);
        } else {

            Capsule::table($this->tableName)->update(
                [
                    'api_login' => $params['api_login'],
                    'api_test' => $params['api_test'],
                    'rate' => $params['rate'],
                    'display_csr_generator' => $params['display_csr_generator'],
                    'auto_install_panel' => $params['auto_install_panel'],
                    'auto_renew_invoice_recurring' => $params['auto_renew_invoice_recurring'],
                    'send_expiration_notification_recurring' => $params['send_expiration_notification_recurring'],
                    'send_expiration_notification_one_time' => $params['send_expiration_notification_one_time'],
                    'renewal_invoice_status_unpaid' => $params['renewal_invoice_status_unpaid'],
                    'visible_renew_button' => $params['visible_renew_button'],
                    'save_activity_logs' => $params['save_activity_logs'],
                    'renew_invoice_days_recurring' => $params['renew_invoice_days_recurring'],
                    'renew_invoice_days_one_time' => $params['renew_invoice_days_one_time'],
                    'summary_expires_soon_days' => $params['summary_expires_soon_days'],
                    'send_certificate_template' => $params['send_certificate_template'],
                    'display_ca_summary' => $params['display_ca_summary'],
                    'sidebar_templates' => $params['sidebar_templates'],
                    'custom_guide' => $params['custom_guide'],
                    'disable_email_validation' => $params['disable_email_validation'],
                    'autorenew_ordertype' => $params['autorenew_ordertype'],
                    'cron_daily' => $params['cron_daily'],
                    'cron_processing' => $params['cron_processing'],
                    'cron_synchronization' => $params['cron_synchronization'],
                    'cron_ssl_summary_stats' => $params['cron_ssl_summary_stats'],
                    'cron_renewal' => $params['cron_renewal'],
                    'cron_send_certificate' => $params['cron_send_certificate'],
                    'cron_price_updater' => $params['cron_price_updater'],
                    'cron_certificate_details_updater' => $params['cron_certificate_details_updater'],
                    'cron_certificate_installer' => $params['cron_certificate_installer'],
                ]);
        }
    }

    public function createApiConfigurationTable()
    {
        if (!Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->create($this->tableName, function ($table) {
                $table->string('api_login');
                $table->boolean('api_test');
                $table->boolean('display_csr_generator');
                $table->boolean('auto_install_panel');
                $table->boolean('auto_renew_invoice_recurring');
                $table->boolean('send_expiration_notification_recurring');
                $table->boolean('send_expiration_notification_one_time');
                $table->boolean('renewal_invoice_status_unpaid');
                $table->boolean('visible_renew_button');
                $table->boolean('save_activity_logs');
                $table->string('autorenew_ordertype')->default('wait_for_payment');
                $table->string('renew_invoice_days_recurring')->nullable();
                $table->string('renew_invoice_days_one_time')->nullable();
                $table->string('summary_expires_soon_days')->nullable();
                $table->integer('send_certificate_template')->nullable();
                $table->boolean('display_ca_summary');
                $table->string('sidebar_templates')->nullable();
                $table->string('rate')->nullable();
                $table->text('custom_guide')->nullable();
                $table->boolean('disable_email_validation');
                $table->boolean('cron_daily')->default(true);
                $table->boolean('cron_processing')->default(true);
                $table->boolean('cron_synchronization')->default(true);
                $table->boolean('cron_ssl_summary_stats')->default(true);
                $table->boolean('cron_renewal')->default(true);
                $table->boolean('cron_send_certificate')->default(true);
                $table->boolean('cron_price_updater')->default(true);
                $table->boolean('cron_certificate_details_updater')->default(true);
                $table->boolean('cron_certificate_installer')->default(true);
            });
        }
    }

    public function updateApiConfigurationTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            if (!Capsule::schema()->hasColumn($this->tableName, 'auto_renew_invoice_recurring')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('auto_renew_invoice_recurring');
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'send_expiration_notification_recurring')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('send_expiration_notification_recurring');
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'send_expiration_notification_one_time')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('send_expiration_notification_one_time');
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'renewal_invoice_status_unpaid')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('renewal_invoice_status_unpaid');
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'visible_renew_button')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('visible_renew_button');
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'save_activity_logs')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('save_activity_logs');
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'renew_invoice_days_recurring')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->string('renew_invoice_days_recurring')->nullable();
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'renew_invoice_days_one_time')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->string('renew_invoice_days_one_time')->nullable();
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'summary_expires_soon_days')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->string('summary_expires_soon_days')->nullable();
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'send_certificate_template')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->integer('send_certificate_template')->nullable();
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'display_ca_summary')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('display_ca_summary');
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'sidebar_templates')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->string('sidebar_templates')->nullable();
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'rate')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->string('rate')->nullable();
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'custom_guide')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->text('custom_guide')->nullable();
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'disable_email_validation')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('disable_email_validation');
                });
            }
            if (!Capsule::schema()->hasColumn($this->tableName, 'auto_install_panel')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('auto_install_panel');
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'api_test')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('api_test');
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_daily')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_daily')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_processing')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_processing')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_synchronization')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_synchronization')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_ssl_summary_stats')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_ssl_summary_stats')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_renewal')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_renewal')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_send_certificate')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_send_certificate')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_price_updater')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_price_updater')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_certificate_details_updater')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_certificate_details_updater')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'cron_certificate_installer')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->boolean('cron_certificate_installer')->default(true);
                });
            }

            if (!Capsule::schema()->hasColumn($this->tableName, 'autorenew_ordertype')) {
                Capsule::schema()->table($this->tableName, function ($table) {
                    $table->string('autorenew_ordertype')->default('wait_for_payment');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_phone_country')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_phone_country');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_firstname')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_firstname');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_lastname')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_lastname');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_organization')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_organization');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_addressline1')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_addressline1');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_phone')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_phone');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_title')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_title');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_phone_country')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_phone_country');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_email')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_email');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_city')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_city');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_country')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_country');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_fax')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_fax');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_postalcode')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_postalcode');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'tech_region')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('tech_region');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'renew_invoice_days_reccuring')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('renew_invoice_days_reccuring');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'send_expiration_notification_reccuring')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('send_expiration_notification_reccuring');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'auto_renew_invoice_days_reccuring')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('auto_renew_invoice_days_reccuring');
                });
            }

            if (Capsule::schema()->hasColumn($this->tableName, 'auto_renew_invoice_one_time')) {
                Capsule::schema()->table($this->tableName, function($table) {
                    $table->dropColumn('auto_renew_invoice_one_time');
                });
            }

            // Encrypt api login
            if (Capsule::schema()->hasColumn($this->tableName, 'api_login')) {
                $apiLogins = Capsule::table($this->tableName)->get('api_login')->pluck('api_login')->toArray();

                foreach ($apiLogins as $apiLogin) {
                    if (base64_decode($apiLogin, true) !== false && str_contains(base64_decode($apiLogin), '/')) {
                        Capsule::table($this->tableName)->where('api_login', '=', $apiLogin)
                            ->update(['api_login' => encrypt($apiLogin)]);
                    }
                }
            }
        }
    }

    public function dropApiConfigurationTable()
    {
        if (Capsule::schema()->hasTable($this->tableName)) {
            Capsule::schema()->dropIfExists($this->tableName);
        }
    }
}
