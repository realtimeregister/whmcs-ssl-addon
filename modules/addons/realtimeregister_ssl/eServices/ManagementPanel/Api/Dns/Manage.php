<?php

declare(strict_types=1);

namespace MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns;

use Exception;
use MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns\Platform\PlatformInterface;
use MGModule\RealtimeRegisterSsl\mgLibs\exceptions\DNSException;

class Manage
{
    /**
     * @var Panel
     */
    protected static $panel;
    private static $instance;

    /**
     * @param \HostcontrolPanel\Manage $panel
     * @param array $options
     * @throws Exception
     */
    public static function loadPanel($panel, $options = [])
    {
        self::$panel = $panel;

        if (!isset(self::$instance)) {
            self::$instance = self::makeInstance(self::$panel, $options);
        }
    }


    /**
     * @param \MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Panel\Manage $panel
     * @return array
     * @throws Exception
     */
    public static function addRecord($panel, array $dcvData)
    {
        foreach ($dcvData as $record) {
            try {
                /** @var PlatformInterface */
                self::$instance->createDNSRecord($record['commonName'], $record['name'], $record['value'], $record['dnsType']);
            } catch (Exception $ex) {
                throw new DNSException($ex->getMessage());
            }
        }

        return 'Record(s) has been successfully added.';
    }

    /**
     * @param array $records
     * @param string $domain
     * @throws Exception
     */
    private static function createRecords($records, $domain)
    {
        foreach ($records as $type => $record) {
            try {
                self::$instance->createDNSRecord($domain, $record['name'], $record['value'], $type);
            } catch (Exception $ex) {
                throw new DNSException($ex->getMessage());
            }
        }
    }


    /**
     * @param \HostcontrolPanel\Manage $panel
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function getPanelRecords($panel, $name)
    {
        self::loadPanel($panel);

        return self::$instance->getDNSRecord($name);
    }

    /**
     * @param \HostcontrolPanel\Manage $panel
     * @param string $domain Domain for which find records
     * @param $expRecords searched record
     * @return bool
     * @throws Exception
     */
    public function checkIfExists($panel, $domain, $expRecords)
    {
        $records = self::getPanelRecords($panel, $domain);
        $allOk = 0;
        foreach ($records as $rec) {
            foreach ($expRecords as $type => $expRecord) {
                if (strtoupper($rec['type']) == $type
                    && strtoupper($rec['name']) == $expRecord['name']
                    && strtoupper($rec['content']) == $expRecord['value']
                ) {
                    $allOk++;
                    continue 2;
                }
            }
        }
        return $allOk == count($expRecords);
    }

    /**
     * @param \HostcontrolPanel\Manage $panel
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    private function makeInstance($panel, $options)
    {
        $panelData = $panel->getPanelData();
        $api = 'MGModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns\Platform\'' . ucfirst($panelData['platform']);

        if (!class_exists($api)) {
            throw new DNSException(sprintf("Platform `%s` not supported.", $panelData['platform']));
        }

        return new $api($panelData + $options);
    }
}
