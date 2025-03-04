<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\addon\admin;

use AddonModule\RealtimeRegisterSsl\addonLibs\forms\CheckboxField;
use AddonModule\RealtimeRegisterSsl\addonLibs\forms\Creator;
use AddonModule\RealtimeRegisterSsl\addonLibs\Lang;
use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractController;
use AddonModule\RealtimeRegisterSsl\models\apiConfiguration\Repository;

class Crons extends AbstractController
{
    private array $fields = [
        'cron_daily', // DailyStatusUpdater
        'cron_processing', // ProcessingOrders
        'cron_synchronization', // AutomaticSynchronisation
        'cron_ssl_summary_stats', // CertificateStatisticsLoader
        'cron_renewal', // ExpiryHandler
        'cron_send_certificate', // CertificateSender
        'cron_price_updater', // PriceUpdater
        'cron_certificate_details_updater', // cron_certificate_details_updater
        'cron_certificate_installer' // InstallCertificates
    ];

    /**
     * @throws \AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System
     */
    public function indexHTML($input = [], $vars = []): array
    {
        $apiConfigRepo = new Repository();
        $input = (array)$apiConfigRepo->get();

        $form = new Creator('crons');

        foreach ($this->fields as $field) {
            $formField = new CheckboxField();
            $formField->name = $field;
            $formField->options = [$field];
            $formField->value = $input[$field] ? [$field] : [''];
            $formField->inline = true;
            $formField->continue = false;
            $formField->enableLabel = false;
            $form->addField($formField);
        }

        $form->addField('submit', 'addon-action', [
            'value' => 'saveItem'
        ]);

        $vars['form'] = $form->getHTML();

        return ['tpl' => 'crons', 'vars' => $vars];
    }

    public function saveItemHTML($input, $vars = [])
    {
        if ($this->checkToken()) {
            try {
                foreach ($this->fields as $field) {
                    if (isset($input[$field])) {
                        $input[$field] = 1;
                    } else {
                        $input[$field] = 0;
                    }
                }

                $apiConfigRepo = new Repository();
                $originalValues = (array)$apiConfigRepo->get();
                $input = array_merge($originalValues, $input);

                $apiConfigRepo->setConfiguration($input);

            } catch (\Exception $ex) {
                $vars['formError'] = Lang::T('messages', $ex->getMessage());
            }
        }
        $vars['success'] = Lang::absoluteT('changessavedsuccessfully');
        return $this->indexHTML($input, $vars);
    }
}
