<?php

namespace MGModule\RealtimeRegisterSsl\models\whmcs\service\configOptions;
use MGModule\RealtimeRegisterSsl as main;

class ConfigOption{
    public $id;
    public $name;
    public $type;
    public $frendlyName;
    public $value;
    public $options = array();
    public $optionsIDs = array();
}