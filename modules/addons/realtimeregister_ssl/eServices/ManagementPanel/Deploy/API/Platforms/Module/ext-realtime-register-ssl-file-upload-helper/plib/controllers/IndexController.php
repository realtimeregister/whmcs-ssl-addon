<?php

class IndexController extends pm_Controller_Action
{
    public function indexAction()
    {
        $this->view->pageTitle = 'Realtime Register SSL file upload helper';
        $actions = json_decode(pm_Settings::get('actions', '[]'), true);
        $this->view->actions = $actions;
    }
}