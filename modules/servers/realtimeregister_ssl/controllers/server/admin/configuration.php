<?php

namespace AddonModule\RealtimeRegisterSsl\controllers\server\admin;

use AddonModule\RealtimeRegisterSsl\mgLibs\forms\ButtonField;
use AddonModule\RealtimeRegisterSsl\mgLibs\forms\CheckboxField;
use AddonModule\RealtimeRegisterSsl\mgLibs\forms\Creator;
use AddonModule\RealtimeRegisterSsl\mgLibs\forms\SelectField;
use AddonModule\RealtimeRegisterSsl\mgLibs\forms\TextField;
use AddonModule\RealtimeRegisterSsl\mgLibs\Lang;
use AddonModule\RealtimeRegisterSsl\mgLibs\process\AbstractController;
use AddonModule\RealtimeRegisterSsl\models\testGroup\testItem\TestItem;
use AddonModule\RealtimeRegisterSsl\models\whmcs\product\Product;

/**
 * Description of configuration
 *
 */
class configuration extends AbstractController
{
    public function indexHTML($input,$vars = [])
    {
        //pobieramy formualrz w kontenetrze default
        $vars['form'] = $this->buildForm($input);

        return [
            'tpl'   => 'configuration',
            'vars' => $vars
        ];
    }
    
    public function saveItemJSON($input, $vars = [])
    {
        $product = new Product($input['params']['pid']);

        $fields = [
            'text_name',
            'text_name2',
            'checkbox_name',
            'onoff',
            'pass',
            'some_option',
            'some_option2',
            'radio_field'
        ];
        
        foreach($fields as $name) {
            $product->configuration()->{$name} = $input[$name];
        }
        
        $product->configuration()->save();
        
        $vars['success'] = Lang::T('savedSuccessfull');
        
        return $vars;
    }
    
    /**
     * Build form
     * @return type
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function buildForm($input)
    {
        $product = new Product($input['params']['pid']);
        
        $form = new Creator('item');
               
        //Dodajemy pole typu text z opisem
        $field = new TextField();
        $field->name = 'text_name';
       // $field->enableDescription = true;
        $field->value = isset($input['text_name'])?$input['text_name']:$product->configuration()->text_name;
        $field->error = $this->getFieldError('textField');
        
        $form->addField($field);
                        
        //alternatywnym opcją dodawania pól jest dodawanie przez stringa nie przez obiekt
        // parametry to:
        // typ pola ( bez końcówki Field ) 
        // nazwa pola
        // array z kofiguracją pola
        $form->addField('text','text_name2', [
            'enableDescription'     => FALSE
            ,'enablePlaceholder'    => true
            ,'error'                => $this->getFieldError('text2')
            ,'value'                => isset($input['text_name2'])?$input['text_name2']:$product->configuration()->text_name2
        ]);
        
        //Dodajemy pole typu checkbox w własnymi labelami dla opcjii
        $field = new CheckboxField();
        $field->name = 'checkbox_name';
        $field->options = TestItem::$avaibleOptionsA;
        //pole typu checkbox moze miec wiele zaznaczonych wartości więc korzystamy z arraya jako value
        $field->value = isset($input['checkbox_name'])?$input['checkbox_name']:$product->configuration()->checkbox_name;
        
        $form->addField($field);
        
        //Dodajemy legendę
        $form->addField('legend','example_legend');
        
        //Pole typu on/off
        $form->addField('onOff','onoff', [
            'value' => isset($input['onoff'])?$input['onoff']:$product->configuration()->onoff
        ]);
        
        //Pole typu password  => do htmla value przekazywane jest jako ********
        // w funkcji updateJSON pokazana jest zalecana metoda porównywania haseł
        $form->addField('password','pass', [
           'value'              => isset($input['pass'])?$input['pass']:$product->configuration()->password
           ,'error'             => $this->getFieldError('password')
        ]);
        
        //pole radio z wymuszonym wyłączoną funkcją translacji opcji
        $form->addField('radio','radio_field', [
           'options'            => TestItem::$avaibleOptionsB
            ,'translateOptions' => FALSE
            ,'value'            => isset($input['radio_field'])?$input['radio_field']:$product->configuration()->radio_field
        ]);
        
        //pole select z włączoną bibloteką JS select2
        $field = new SelectField();
        $field->name = 'some_option';
        $field->options = [1,2,3,4,5];
        $field->select2 = true;
        $field->value = isset($input['some_option'])?$input['some_option']:$product->configuration()->some_option;

        $form->addField($field);
        
        //pole select typu multiple ( zalecane uzycie w polaczeniu z select 2 )
        //ludzie sa glupi i select z ctrl moze ich przerosnac
        $field = new SelectField();
        $field->name = 'some_option2';
        $field->multiple = true;
        $field->select2 = true;
        $field->options = TestItem::$avaibleOptionsC;
        //należy pamiętac że jeśli pole jest typu multiselet to value dajemy jako array
        $field->value = isset($input['some_option2'])?$input['some_option2']:$product->configuration()->some_option2;
        $field->error = $this->getFieldError('option2');
        $form->addField($field);
        
        //pole typu button 
        //continue oznacz że kolejne pola będą dodawane w tym samym wierszu 
        //w takim przpadku należy ręcznie sterować szerokością pola 
        //col width oznacza końcówkę nazwy klasy col-sm- ( domyślnie 4 )
        //color oznacza nazwe koloru btn-
        //textlable włacza text w labelu pola ( domyślnie jest pusty )
        $field = new ButtonField();
        $field->name = 'test';
        $field->colWidth = 3;
        $field->continue = true;
        $field->textLabel = true;
        $field->color = 'orange';
         
        $form->addField($field);
        
        //kolejne pole continue 
        $field = new ButtonField();
        $field->name = 'test2';
        $field->colWidth = 3;
        $field->continue = true;
        $field->color = 'danger';
        $form->addField($field);
        
        //to pole zostanie dodane na końcu a kolejne po nim bedą dodawne juz w nowej lini
        $field = new ButtonField();
        $field->name = 'test3';     
        $field->colWidth = 3;
        $field->color = 'primary';
        $form->addField($field);

        //pole button submit
        $form->addField('submit','mg-action', [
            'value'     => 'saveItem'
        ]);
        
        return $form->getHTML('modal');
    }
}
