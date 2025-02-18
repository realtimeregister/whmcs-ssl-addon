<?php

declare(strict_types=1);

namespace AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns;

use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\DNSException;
use Exception;

class Manage
{
    /**
     * @var Panel
     */
    protected static $panel;
    private static $instance;

    /**
     * @param array $panel
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
     * @param array $records Records [$type => ['name' => $name, 'value' => $value]]
     * @throws Exception
     */
    public static function addRecord(array $panel, string $domain, array $dcvRecord)
    {
        self::loadPanel($panel);

        self::createRecord($dcvRecord, $domain);

        return 'Record(s) has been successfully added.';
    }

    /**
     * @throws Exception
     */
    private static function createRecord(array $record, string $domain)
    {
        try {
            self::$instance->createDNSRecord($domain, $record['dnsRecord'], $record['dnsContents'], $record['dnsType']);
        } catch (Exception $ex) {
            throw new DNSException($ex->getMessage());
        }
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getPanelRecords(array $panel, string $name)
    {
        self::loadPanel($panel);

        return self::$instance->getDNSRecord($name);
    }

    /**
     * @param array $panel
     * @param string $domain Domain for which find records
     * @param array $expRecords searched record
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
     * @param array $panel
     * @param array $options
     * @return mixed
     * @throws Exception
     */
    private static function makeInstance($panel, $options)
    {
        $api = 'AddonModule\RealtimeRegisterSsl\eServices\ManagementPanel\Api\Dns\Platform\\' . ucfirst($panel['platform']);

        if (!class_exists($api)) {
            throw new DNSException(sprintf("Platform `%s` not supported.", $panel['platform']));
        }

        return new $api($panel + $options);
    }
}
