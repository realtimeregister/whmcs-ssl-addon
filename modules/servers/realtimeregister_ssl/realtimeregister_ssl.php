<?php

use MGModule\RealtimeRegisterSsl\eProviders\ApiProvider;
use MGModule\RealtimeRegisterSsl\eServices\FlashService;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminCustomButtonArray;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminRecheckCertificateDetails;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminReissueCertificate;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminResendApproverEmail;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminResendCertificate;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminServicesTabFields;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminViewCertyfifcate;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\ClientContactDetails;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\ClientReissueCertificate;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\Renew;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepThree;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepTwo;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepTwoJS;
use MGModule\RealtimeRegisterSsl\eServices\provisioning\TerminateAccount;
use MGModule\RealtimeRegisterSsl\Loader;
use MGModule\RealtimeRegisterSsl\mgLibs\Lang;
use MGModule\RealtimeRegisterSsl\Server;
use SandwaveIo\RealtimeRegister\Api\CertificatesApi;
use SandwaveIo\RealtimeRegister\Api\ProcessesApi;

if(!defined('DS'))define('DS',DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . 'Loader.php';
new Loader();
MGModule\RealtimeRegisterSsl\Server::I();

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function realtimeregister_ssl_MetaData() {
    return [
        'DisplayName' => 'Realtime Register Ssl Server WHMCS',
        'APIVersion' => '1.0',
    ];
}

function realtimeregister_ssl_ConfigOptions() {
    $configOptions = new ConfigOptions();
    return $configOptions->run();
}

function realtimeregister_ssl_CreateAccount($params) {
    $createAccount = new MGModule\RealtimeRegisterSsl\eServices\provisioning\CreateAccount($params);
    return $createAccount->run();
}

function realtimeregister_ssl_SuspendAccount($params) {
    $suspendAccount = new MGModule\RealtimeRegisterSsl\eServices\provisioning\SuspendAccount($params);
    return $suspendAccount->run();
}

function realtimeregister_ssl_UnsuspendAccount($params) {
    $unsuspendAccount = new MGModule\RealtimeRegisterSsl\eServices\provisioning\UnsuspendAccount($params);
    return $unsuspendAccount->run();
}

function realtimeregister_ssl_SSLStepOne($params) {
    $SSLStepOne = new MGModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepOne($params);
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

function realtimeregister_ssl_AdminCustomButtonArray() {
    $adminCustomButtonArray = new AdminCustomButtonArray();
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

function realtimeregister_ssl_Renew($params) {
    $renewCertificate = new Renew($params);
    return $renewCertificate->run();
}

function realtimeregister_ssl_AdminServicesTabFields(array $params) {
    $adminServiceJS = new AdminServicesTabFields($params);
    return $adminServiceJS->run();
}

function realtimeregister_ssl_SSLAdminGetCertificate($p) {
    return MGModule\RealtimeRegisterSsl\eServices\provisioning\GetCertificate::runBySslId($p['serviceid']);
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
    $adminViewCertyfifcate = new AdminViewCertyfifcate($_POST);
    $adminViewCertyfifcate->run();
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
        echo MGModule\RealtimeRegisterSsl\Server::getJSONClientAreaPage($params, $_REQUEST);
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
