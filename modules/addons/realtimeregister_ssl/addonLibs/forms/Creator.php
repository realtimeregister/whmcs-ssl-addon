<?php

namespace AddonModule\RealtimeRegisterSsl\addonLibs\forms;
use AddonModule\RealtimeRegisterSsl as main;
use AddonModule\RealtimeRegisterSsl\addonLibs\exceptions\System;


/**
 * Form Creator
 *
 * @SuppressWarnings(PHPMD)
 */
class Creator
{
    /**
     *
     * @var abstractField[]
     */
    public $fields = [];
    public $hidden = [];
    public $name;
    public $url = null;
    public $addFormNameToFields = false;
    public $addIDs = true;
    public $autoPrepare = true;
    public $getHTMLCount = 0;
    public $lastID = null;

    public function __construct($name, $options = [])
    {
        $this->name = $name;
        $this->addIDs = $name;

        foreach ($options as $name => $value) {
            if (property_exists($this, $name)) {
                $this->{$name} = $value;
            }
        }

        $this->hidden[] = new HiddenField([
            'name' => 'addon-token'
        ,
            'value' => md5(time())
        ]);
    }

    /**
     *
     * @param AbstractField|string $field
     * @param type $dataOrName
     * @param type $data
     * @throws System
     */
    public function addField($field, $dataOrName = null, $data = []): void
    {
        if (is_string($dataOrName)) {
            $data['name'] = $dataOrName;
        } elseif (is_array($dataOrName)) {
            $data = $dataOrName;
        }

        $data['formName'] = $this->name;

        if (is_object($field)) {
            if (get_parent_class($field) !== __NAMESPACE__ . '\\' . 'AbstractField') {
                throw new System('Unable to use this object as form field');
            }

            if ($field->type == 'hidden') {
                $this->hidden[] = $field;
            } else {
                $this->fields[] = $field;
            }
        } elseif (is_string($field) && is_array($data)) {
            $field = ucfirst($field);
            $className = __NAMESPACE__ . '\\' . $field . 'Field';

            if (!class_exists($className)) {
                throw new System('Unable to crate form field type:' . $className);
            }

            $field = new $className($data);

            $field->formName = $this->name;

            if ($field->type == 'hidden') {
                $this->hidden[] = $field;
            } else {
                $this->fields[] = $field;
            }
        } else {
            throw new System('Unable create form field object');
        }
    }

    public function anyField(): bool
    {
        return !empty($this->fields);
    }

    public function prepare(): void
    {
        foreach ($this->fields as &$field) {
            $field->html = null;
            $field->addFormNameToFields = $this->addFormNameToFields;
            $field->addIDs = $this->addIDs;
            $field->formName = $this->name;
        }
    }

    public function setIDs($id): void
    {
        $this->addIDs = $id;
    }

    public function getHTML($container = 'default', $data = []): string
    {
        main\addonLibs\Lang::stagCurrentContext('generateForm');
        main\addonLibs\Lang::addToContext($this->name);

        if ($this->autoPrepare) {
            $this->addIDs .= '_' . $container;
            $this->prepare();
        }
        $closedTag = true;

        foreach ($this->fields as $field) {
            if (empty($field->html)) {
                if ($closedTag) {
                    $field->opentag = true;
                } else {
                    $field->opentag = false;
                    $closedTag = true;
                }

                if ($field->continue) {
                    $closedTag = $field->closetag = false;
                } else {
                    $field->closetag = true;
                }

                $field->generate();
            }
        }

        foreach ($this->hidden as $field) {
            if (empty($field->html)) {
                $field->generate();
            }
        }

        $data['name'] = $this->name;
        $data['url'] = $this->url;
        $data['fields'] = $this->fields;
        $data['hidden'] = $this->hidden;

        $html = main\addonLibs\Smarty::I()->view(
            $container,
            $data,
            main\addonLibs\process\MainInstance::getModuleTemplatesDir() . DS . 'formFields' . DS . 'containers'
        );

        main\addonLibs\Lang::unstagContext('generateForm');

        $this->getHTMLCount++;

        return $html;
    }

    public function deleteFields(): void
    {
        $this->fields = [];
        $this->hidden = [];
    }

    /**
     *
     * @param type $prefix
     * @return Creator
     */
    public function rebuildFieldIds($prefix)
    {
        foreach ($this->fields as $field) {
            if ($field->id) {
                $field->id .= $prefix;
                $field->html = null;
            }
        }

        return $this;
    }
}
