<?php

namespace AddonModule\RealtimeRegisterSsl;

use AddonModule\RealtimeRegisterSsl\addonLibs\error\Register;
use AddonModule\RealtimeRegisterSsl\addonLibs\process\AbstractMainDriver;
use Exception;

class Addon extends AbstractMainDriver
{
    /**
     * Load Addon WHMCS Configuration
     */
    public function loadAddonConfiguration()
    {
        $result = addonLibs\MySQL\Query::select(
            [
                'setting',
                'value'
            ],
            'tbladdonmodules',
            [
                'module' => $this->configuration()->systemName
            ]
        );

        while ($row = $result->fetch()) {
            $this->configuration()->{$row['setting']} = $row['value'];
        }
    }

    /**
     * Return Tempalates Path
     *
     * @param bool $relative
     * @return string
     */
    public static function getModuleTemplatesDir($relative = false)
    {
        $dir = ($relative) ? '' : (__DIR__ . DS);

        $dir .= 'templates' . DS;

        if (self::I()->isAdmin()) {
            return $dir . 'admin';
        } else {
            $template = $GLOBALS['CONFIG']['Template'];

            if (file_exists(__DIR__ . DS . 'templates' . DS . 'clientarea' . DS . $template)) {
                return $dir . 'clientarea' . DS . $template;
            } else {
                return $dir . 'clientarea' . DS . 'default';
            }
        }
    }

    public function getAssetsURL()
    {
        if ($this->isAdmin()) {
            return '../modules/addons/' . $this->configuration()->systemName . '/templates/admin/assets';
        } else {
            return 'modules/addons/' . $this->configuration()->systemName . '/' . self::getModuleTemplatesDir(true)
                . '/assets';
        }
    }

    public function getType()
    {
        return 'addon';
    }

    public static function getMainDIR()
    {
        return __DIR__;
    }

    public static function getUrl($page = null, $action = null, $params = [])
    {
        if (self::I()->isAdmin()) {
            $url = 'addonmodules.php?module=' . self::I()->configuration()->systemName;
        } else {
            $url = 'index.php?m=' . self::I()->configuration()->systemName;
        }

        if ($page) {
            $url .= '&addon-page=' . $page;
            if ($action) {
                $url .= '&addon-action=' . $action;
            }

            if ($params) {
                $url .= '&' . http_build_query($params);
            }
        }

        return $url;
    }

    public static function genCustomPageUrl($page = null, $action = null, $params = [])
    {
        if (self::I()->isAdmin()) {
            $url = 'addonmodules.php?module=' . self::I()->configuration()->systemName . '&customPage=1';
        } else {
            $url = 'index.php?m=' . self::I()->configuration()->systemName . '&customPage=1';
        }

        if ($page) {
            $url .= '&addon-page=' . $page;
        }

        if ($action) {
            $url .= '&addon-action=' . $action;
        }

        if ($params) {
            $url .= '&' . http_build_query($params);
        }

        return $url;
    }

    public static function genJSONUrl($page)
    {
        if (self::I()->isAdmin()) {
            return 'addonmodules.php?module=' . self::I()->configuration()->systemName . '&json=1&addon-page=' . $page;
        } else {
            return 'index.php?m=' . self::I()->configuration()->systemName . '&json=1&addon-page=' . $page;
        }
    }

    public static function config()
    {
        return array
        (
            'name' => self::I()->configuration()->name,
            'description' => self::I()->configuration()->description,
            'version' => self::I()->configuration()->version,
            'author' => self::I()->configuration()->getAuthor(),
            'fields' => self::I()->configuration()->getAddonWHMCSConfig()
        );
    }

    public static function activate()
    {
        try {
            self::I()->configuration()->activate();

            return [
                'status' => 'success'
            ];
        } catch (Exception $ex) {
            return [
                'status' => 'error',
                'description' => $ex->getMessage()
            ];
        }
    }

    public static function deactivate()
    {
        try {
            self::I()->configuration()->deactivate();

            return [
                'status' => 'success'
            ];
        } catch (Exception $ex) {
            return [
                'status' => 'error',
                'description' => $ex->getMessage()
            ];
        }
    }

    public static function upgrade($vars)
    {
        try {
            self::I()->configuration()->upgrade($vars);
        } catch (Exception $ex) {
            self::dump($ex);
            models\whmcs\errors\Register::register($ex);
            return ["error" => $ex->getMessage()];
        }
    }

    public static function getHTMLAdminCustomPage($input)
    {
        try {
            self::I()->isAdmin(true);
            self::I()->setMainLangContext();

            $page = empty($input['addon-page']) ? 'Home' : $input['addon-page'];
            $page = ucfirst($page);
            $action = empty($input['addon-action']) ? 'index' : $input['addon-action'];

            list($content) = self::I()->runController($page, $action, $input, 'CustomHTML');
            return $content;
        } catch (Exception $ex) {
            self::dump($ex);
            addonLibs\Smarty::I()->setTemplateDir(self::I()->getModuleTemplatesDir());

            $message = $ex->getMessage();
            if (method_exists($ex, 'getToken')) {
                $message .= addonLibs\Lang::absoluteT('token') . $ex->getToken();
            }

            return addonLibs\Smarty::I()->view('fatal', [
                'message' => $message
            ]);
        }
    }

    public static function getHTMLAdminPage($input)
    {
        try {
            self::I()->isAdmin(true);
            self::I()->setMainLangContext();

            if (self::I()->isDebug()) {
                self::I()->configuration()->activate();
            }

            $menu = [];
            foreach (self::I()->configuration()->getAddonMenu() as $catName => $category) {
                // display the page or not
                if (strpos($catName, "documentation") === false) {
                    $className = self::I()->getMainNamespace() . "\\controllers\\" . self::I()->getType()
                        . "\\" . 'admin' . "\\" . ucfirst(
                            $catName
                        );
                    $controller = new $className();
                    if (method_exists($controller, "isActive") && !$controller->{"isActive"}()) {
                        continue;
                    }
                }


                if (isset($category['submenu'])) {
                    foreach ($category['submenu'] as $subName => &$subPage) {
                        if (empty($subPage['url'])) {
                            $subPage['url'] = self::getUrl($catName, $subName);
                        }
                    }
                }

                $category['url'] = self::getUrl($catName);

                $menu[$catName] = $category;
            }


            if (empty($input['addon-page'])) {
                $input['addon-page'] = key($menu);
            }

            if ($input['addon-page']) {
                $breadcrumb[0] = [
                    'name' => $input['addon-page'],
                    'url' => $menu[$input['addon-page']]['url'],
                    'icon' => $menu[$input['addon-page']]['icon']
                ];
                if ($input['addon-action']) {
                    $breadcrumb[1] = [
                        'name' => $input['addon-action'],
                        'url' => $menu[$input['addon-page']]['submenu'][$input['addon-action']]['url'],
                        'icon' => $menu[$input['addon-page']]['submenu'][$input['addon-action']]['icon']
                    ];
                }
            }


            $page = $input['addon-page'];
            $action = empty($input['addon-action']) ? 'index' : $input['addon-action'];
            $page = ucfirst($page);
            $vars = [
                'assetsURL' => self::I()->getAssetsURL(),
                'mainURL' => self::I()->getUrl(),
                'mainName' => self::I()->configuration()->name,
                'menu' => $menu,
                'breadcrumbs' => $breadcrumb,
                'JSONCurrentUrl' => self::I()->genJSONUrl($page),
                'currentPageName' => $page,
                'Addon' => self::I(),
            ];

            try {
                list($content, $success, $error) = self::I()->runController($page, $action, $input, 'HTML');
                $vars['content'] = $content;
                $vars['success'] = $success;
                $vars['error'] = $error;
            } catch (Exception $ex) {
                self::dump($ex);
                Register::register($ex);
                $vars['error'] = $ex->getMessage();
                if (method_exists($ex, 'getToken')) {
                    $vars['error'] .= addonLibs\Lang::absoluteT('token') . $ex->getToken();
                }
            }

            addonLibs\Smarty::I()->setTemplateDir(self::I()->getModuleTemplatesDir());

            $html = addonLibs\Smarty::I()->view('main', $vars);

            if (self::I()->isDebug()) {
                $tmp = '<div style="color: #a94442;background-color: #f2dede;border-color: #dca7a7;font-size:20px;padding:10px;"><strong>Module is under development Mode!!!!!!!!!!!!!!!</strong></div>';

                if ($langs = addonLibs\Lang::getMissingLangs()) {
                    $tmp .= '<pre>';
                    foreach ($langs as $lk => $lang) {
                        $tmp .= $lk . " = '" . $lang . "';\n";
                    }
                    $tmp .= '</pre>';
                }
                $html = $tmp . $html;
            }

            return $html;
        } catch (Exception $ex) {
            self::dump($ex);

            Register::register($ex);
            addonLibs\Smarty::I()->setTemplateDir(self::I()->getModuleTemplatesDir());

            $message = $ex->getMessage();
            if (method_exists($ex, 'getToken')) {
                $message .= addonLibs\Lang::absoluteT('token') . $ex->getToken();
            }

            return addonLibs\Smarty::I()->view('fatal', [
                'message' => $message,
                'assetsURL' => self::I()->getAssetsURL()

            ]);
        }
    }

    public static function getHTMLClientAreaPage($input)
    {
        $menu = [];
        foreach (self::I()->configuration()->getClienMenu() as $catName => $category) {
            // display the page or not
            if (strpos($catName, "documentation") === false) {
                $className = self::I()->getMainNamespace() . "\\controllers\\" . self::I()->getType(
                    ) . "\\" . 'clientarea' . "\\" . ucfirst($catName);
                $controller = new $className ();
                if (method_exists($controller, "isActive") && !$controller->{"isActive"}()) {
                    continue;
                }
            }
            if (isset($category['submenu'])) {
                foreach ($category['submenu'] as $subName => &$subPage) {
                    if (empty($subPage['url'])) {
                        $subPage['url'] = self::getUrl($catName, $subName);
                    }
                }
            }

            $category['url'] = self::getUrl($catName);

            $menu[$catName] = $category;
        }

        if (empty($input['addon-page'])) {
            $input['addon-page'] = key($menu);
        }

        $output = [
            'pagetitle' => self::I()->configuration()->clientareaName,
            'templatefile' => self::I()->getModuleTemplatesDir(true) . '/main',
            'requirelogin' => isset($_SESSION['uid']) ? false : true,
        ];

        $breadcrumb = [self::I()->getUrl() => self::I()->configuration()->clientareaName];

        try {
            self::I()->setMainLangContext();

            $page = ucfirst($input['addon-page']);
            if (!empty($input['addon-page'])) {
                $url = self::I()->getUrl($input['addon-page']);
                $breadcrumb[$url] = $input['addon-page'];
            }
            $action = empty($input['addon-action']) ? 'index' : $input['addon-action'];
            $vars = [
                'assetsURL' => self::I()->getAssetsURL(),
                'mainURL' => self::I()->getUrl(),
                'mainName' => self::I()->configuration()->clientareaName,
                'JSONCurrentUrl' => self::I()->genJSONUrl($page),
                'currentPageName' => strtolower($page),
                'menu' => $menu,
                'breadcrumbs' => $breadcrumb,
            ];

            try {
                $vars['ADDONLANG'] = addonLibs\Lang::getInstance();
                list($content, $success, $error) = self::I()->runController($page, $action, $input, 'HTML');

                if (self::I()->isDebug()) {
                    $html = '<div style="color: #a94442;background-color: #f2dede;border-color: #dca7a7;font-size:20px;padding:10px;"><strong>Module is under development Mode!!!!!!!!!!!!!!!</strong></div>';

                    if ($langs = addonLibs\Lang::getMissingLangs()) {
                        $html .= '<pre>';
                        foreach ($langs as $lk => $lang) {
                            $html .= $lk . " = '" . $lang . "';\n";
                        }
                        $html .= '</pre>';
                    }

                    $content = $html . $content;
                }


                $vars['content'] = $content;
                $vars['success'] = $success;
                $vars['error'] = $error;
            } catch (Exception $ex) {
                self::dump($ex);
                Register::register($ex);
                $vars['error'] = addonLibs\Lang::absoluteT($ex->getMessage());
                if (method_exists($ex, 'getToken')) {
                    $vars['error'] .= addonLibs\Lang::absoluteT($ex->getMessage());
                }
            }
        } catch (Exception $ex) {
            self::dump($ex);
            Register::register($ex);
            $vars['error'] = addonLibs\Lang::absoluteT('generalError');
            if (method_exists($ex, 'getToken')) {
                $vars['error'] .= addonLibs\Lang::absoluteT('token') . $ex->getToken();
            }
        }

        $output['breadcrumb'] = $breadcrumb;
        $output['vars'] = $vars;

        return $output;
    }

    public static function getJSONAdminPage($input)
    {
        $content = [];

        $page = 'home';
        if (!empty($input['addon-page'])) {
            $page = $input['addon-page'];
        }
        $page = ucfirst($page);
        $action = empty($input['addon-action']) ? 'index' : $input['addon-action'];
        try {
            self::I()->isAdmin(true);
            self::I()->setMainLangContext();

            list($result, $success, $error) = self::I()->runController($page, $action, $input, 'JSON');

            if ($error) {
                $content['error'] = $error;
                $content['result'] = 'error';
            } elseif ($success) {
                $content['success'] = $success;
                $content['result'] = 'success';
            }

            if ($langs = addonLibs\Lang::getMissingLangs()) {
                $html = '<pre>';
                foreach ($langs as $lk => $lang) {
                    $html .= $lk . " = '" . $lang . "';\n";
                }
                $html .= '</pre>';

                $content['error'] = $html;
                $content['result'] = 'error';
            }

            $content['data'] = $result;
        } catch (Exception $ex) {
            self::dump($ex);
            Register::register($ex);
            $content['result'] = 'error';
            $content['error'] = $ex->getMessage();
            if (method_exists($ex, 'getToken')) {
                $content['error'] .= addonLibs\Lang::absoluteT('token') . $ex->getToken();
            }
        }

        return '<JSONRESPONSE#' . json_encode($content) . '#ENDJSONRESPONSE>';
    }

    public static function getJSONClientAreaPage($input)
    {
        $content = [];

        $page = 'home';
        if (!empty($input['addon-page'])) {
            $page = $input['addon-page'];
        }
        $page = ucfirst($page);
        $action = empty($input['addon-action']) ? 'index' : $input['addon-action'];

        try {
            self::I()->setMainLangContext();

            list($result, $success, $error) = self::I()->runController($page, $action, $input, 'JSON');

            if ($error) {
                $content['error'] = $error;
                $content['result'] = 'error';
            } elseif ($success) {
                $content['success'] = $success;
                $content['result'] = 'success';
            }

            if ($langs = addonLibs\Lang::getMissingLangs()) {
                $html = '<pre>';
                foreach ($langs as $lk => $lang) {
                    $html .= $lk . " = '" . $lang . "';\n";
                }
                $html .= '</pre>';

                $content['error'] = $html;
                $content['result'] = 'error';
            }

            $content['data'] = $result;
        } catch (Exception $ex) {
            self::dump($ex);
            $content['result'] = 'error';
            Register::register($ex);
            $content['error'] = addonLibs\Lang::absoluteT('generalError');
            if (method_exists($ex, 'getToken')) {
                $content['error'] .= addonLibs\Lang::absoluteT('token') . $ex->getToken();
            }
        }

        return '<JSONRESPONSE#' . json_encode($content) . '#ENDJSONRESPONSE>';
    }

    public static function localAPI($action, $arguments)
    {
        $output = [
            'action' => $action
        ];

        try {
            self::I()->isAdmin(true);
            self::I()->setMainLangContext();

            list($result) = self::I()->runController('localAPI', $action, $arguments, 'API');
            $output['success'] = $result;
        } catch (Exception $ex) {
            self::dump($ex);
            Register::register($ex);
            $output['error'] = [
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            ];
        }

        return $output;
    }
}
