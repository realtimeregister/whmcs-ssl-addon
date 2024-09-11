<?php

use AddonModule\RealtimeRegisterSsl\eServices\FlashService;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\AdminCustomButtonArray;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\AdminRecheckCertificateDetails;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\AdminReissueCertificate;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\AdminResendApproverEmail;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\AdminResendCertificate;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\AdminServicesTabFields;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\AdminViewCertificate;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ClientContactDetails;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ClientReissueCertificate;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\Renew;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepThree;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepTwo;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepTwoJS;
use AddonModule\RealtimeRegisterSsl\eServices\provisioning\TerminateAccount;
use AddonModule\RealtimeRegisterSsl\Loader;
use AddonModule\RealtimeRegisterSsl\mgLibs\Lang;
use AddonModule\RealtimeRegisterSsl\Server;

if(!defined('DS'))define('DS',DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . 'Loader.php';
new Loader();
AddonModule\RealtimeRegisterSsl\Server::I();

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function realtimeregister_ssl_MetaData() {
    return [
        'DisplayName' => 'Realtime Register SSL Server WHMCS',
        'APIVersion' => '1.0',
    ];
}

function realtimeregister_ssl_ConfigOptions() {
    $configOptions = new ConfigOptions();
    return $configOptions->run();
}

function realtimeregister_ssl_CreateAccount($params) {
    $createAccount = new AddonModule\RealtimeRegisterSsl\eServices\provisioning\CreateAccount($params);
    return $createAccount->run();
}

function realtimeregister_ssl_SuspendAccount($params) {
    $suspendAccount = new AddonModule\RealtimeRegisterSsl\eServices\provisioning\SuspendAccount($params);
    return $suspendAccount->run();
}

function realtimeregister_ssl_UnsuspendAccount($params) {
    $unsuspendAccount = new AddonModule\RealtimeRegisterSsl\eServices\provisioning\UnsuspendAccount($params);
    return $unsuspendAccount->run();
}

function realtimeregister_ssl_SSLStepOne($params) {
    $SSLStepOne = new AddonModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepOne($params);
    return $SSLStepOne->run();
}

function realtimeregister_ssl_SSLStepTwo($params) {
    try{
        $SSLStepTwo = new SSLStepTwo($params);
        if(isset($_POST['privateKey']) && $_POST['privateKey'] != null) {
            $SSLStepTwo->setPrivateKey($_POST['privateKey']);
        }

        $step2 = $SSLStepTwo->run();
        $step2['approveremails'] = $step2['approveremails2'];
        return $step2;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
function realtimeregister_ssl_SSLStepTwoJS($params) {
    $SSLStepTwoJS = new SSLStepTwoJS($params);
    return $SSLStepTwoJS->run();
}

function realtimeregister_ssl_SSLStepThree($params) {
   $SSLStepThree = new SSLStepThree($params);
    return $SSLStepThree->run();
}

function realtimeregister_ssl_TerminateAccount($params) {
    $terminateAccount = new TerminateAccount($params);
    return $terminateAccount->run();
}

function realtimeregister_ssl_AdminCustomButtonArray($params) {
    $adminCustomButtonArray = new AdminCustomButtonArray($params);
    return $adminCustomButtonArray->run();
}

function realtimeregister_ssl_SSLAdminResendApproverEmail($params) {
    $resendApproverEmail = new AdminResendApproverEmail($params);
    return $resendApproverEmail->run();
}

function realtimeregister_ssl_SSLAdminResendCertificate($params) {
    $adminResendCertificate = new AdminResendCertificate($params);
    return $adminResendCertificate->run();
}

/**
 * Renew an instance of a product/service.
 *
 * Attempt to renew an existing instance of a given product/service. This is
 * called any time a product/service invoice has been paid.
 *
 * @param array $params common module parameters
 *
 * @see https://developers.whmcs.com/provisioning-modules/module-parameters/
 *
 * @return string "success" or an error message
 */
function realtimeregister_ssl_Renew($params) {
    $renewCertificate = new Renew($params);
    return $renewCertificate->run();
}

function realtimeregister_ssl_AdminServicesTabFields(array $params) {
    $adminServiceJS = new AdminServicesTabFields($params);
    return $adminServiceJS->run();
}

function realtimeregister_ssl_SSLAdminGetCertificate($p) {
    return AddonModule\RealtimeRegisterSsl\eServices\provisioning\GetCertificate::runBySslId($p['serviceid']);
}

function realtimeregister_ssl_FlashErrorStepOne() {
    $errors = FlashService::getStepOneError();
    if(isset($errors['errormessage']) && !empty($errors['errormessage']))
    {
        // WHMCS v7.2
       global $smartyvalues; 
       $smartyvalues['errormessage'] = $errors['errormessage'];
       
       // < WHMCS v7.2
       global $smarty;
       $smarty->assign('errormessage', $errors['errormessage']);
    }
}

if (isset($_POST['reissueModal'], $_SESSION['adminid']) && $_POST['reissueModal'] === 'yes' && $_SESSION['adminid'] ) {
    $adminReissueCertificate = new AdminReissueCertificate($_POST);
    $adminReissueCertificate->run();   
}

if (isset($_POST['recheckModal'], $_SESSION['adminid']) && $_POST['recheckModal'] === 'yes' && $_SESSION['adminid']) {
    $adminCheckCertificateDetails = new AdminRecheckCertificateDetails($_POST);
    $adminCheckCertificateDetails->run();
}

if (isset($_POST['viewModal'], $_SESSION['adminid']) && $_POST['viewModal'] === 'yes' && $_SESSION['adminid']) {
    $adminViewCertificate = new AdminViewCertificate($_POST);
    $adminViewCertificate->run();
}

function realtimeregister_ssl_ClientAreaCustomReissueCertificate($params)
{
    $clientReissueCertificate = new ClientReissueCertificate($params, $_POST, $_GET);
    return $clientReissueCertificate->run();
}

function realtimeregister_ssl_ClientAreaCustomContactDetails($params)
{
    $clientReissueCertificate = new ClientContactDetails($params, $_POST, $_GET);
    return $clientReissueCertificate->run();
}

function realtimeregister_ssl_ClientArea(array $params)
{
    if (!empty($_REQUEST['json'])) {
        header('Content-Type: text/plain');
        echo AddonModule\RealtimeRegisterSsl\Server::getJSONClientAreaPage($params, $_REQUEST);
        die();
    }
    
    return Server::getHTMLClientAreaPage($params, $_REQUEST);
}

function realtimeregister_ssl_ClientAreaCustomButtonArray()
{
    $lang = Lang::getInstance();
    return [
        $lang->T('Reissue Certificate') => 'ClientAreaCustomReissueCertificate'
    ];
}
add_hook('ClientAreaHeadOutput', 1, 'realtimeregister_ssl_SSLStepTwoJS');
add_hook('ClientAreaHeadOutput', 9999999999, 'realtimeregister_ssl_FlashErrorStepOne');
