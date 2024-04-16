<?php

if(!defined('DS'))define('DS',DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . 'Loader.php';
new \MGModule\RealtimeRegisterSsl\Loader();
MGModule\RealtimeRegisterSsl\Server::I();

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function RealtimeRegisterSsl_MetaData() {
    return array(
        'DisplayName' => 'Realtime Register Ssl WHMCS',
        'APIVersion' => '1.0',
    );
}

function RealtimeRegisterSsl_ConfigOptions() {
    $configOptions = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\ConfigOptions();
    return $configOptions->run();
}

function RealtimeRegisterSsl_CreateAccount($params) {
    $createAccount = new MGModule\RealtimeRegisterSsl\eServices\provisioning\CreateAccount($params);
    return $createAccount->run();
}

function RealtimeRegisterSsl_SuspendAccount($params) {
    $suspendAccount = new MGModule\RealtimeRegisterSsl\eServices\provisioning\SuspendAccount($params);
    return $suspendAccount->run();
}

function RealtimeRegisterSsl_UnsuspendAccount($params) {
    $unsuspendAccount = new MGModule\RealtimeRegisterSsl\eServices\provisioning\UnsuspendAccount($params);
    return $unsuspendAccount->run();
}

function RealtimeRegisterSsl_SSLStepOne($params) {
    $SSLStepOne = new MGModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepOne($params);
    return $SSLStepOne->run();
}

function RealtimeRegisterSsl_SSLStepTwo($params) {
    try{
    
        $SSLStepTwo = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepTwo($params);
        if(isset($_POST['privateKey']) && $_POST['privateKey'] != null) {
            $SSLStepTwo->setPrivateKey($_POST['privateKey']);
        }

        $step2 = $SSLStepTwo->run();
        $step2['approveremails'] = $step2['approveremails2'];
        return $step2;
        
    } catch (\Exception $e) {

        return $e->getMessage();
        
    }
}
function RealtimeRegisterSsl_SSLStepTwoJS($params) {
    $SSLStepTwoJS = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepTwoJS($params);
    return $SSLStepTwoJS->run();
}

function RealtimeRegisterSsl_SSLStepThree($params) {
   $SSLStepThree = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\SSLStepThree($params);
    return $SSLStepThree->run();
}

function RealtimeRegisterSsl_TerminateAccount($params) {
    $terminateAccount = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\TerminateAccount($params);
    return $terminateAccount->run();
}

function RealtimeRegisterSsl_AdminCustomButtonArray() {
    $adminCustomButtonArray = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminCustomButtonArray();
    return $adminCustomButtonArray->run();
}

function RealtimeRegisterSsl_SSLAdminResendApproverEmail($params) {
    $resendApproverEmail = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminResendApproverEmail($params);
    return $resendApproverEmail->run();
}

function RealtimeRegisterSsl_SSLAdminResendCertificate($params) {
    $adminResendCertificate = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminResendCertificate($params);
    return $adminResendCertificate->run();
}

function RealtimeRegisterSsl_Renew($params) {
    $renewCertificate = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\Renew($params);
    return $renewCertificate->run();
}

function RealtimeRegisterSsl_AdminServicesTabFields(array $params) {
    $adminServiceJS = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminServicesTabFields($params);
    return $adminServiceJS->run();
}

function RealtimeRegisterSsl_SSLAdminGetCertificate($p) {
    return MGModule\RealtimeRegisterSsl\eServices\provisioning\GetCertificate::runBySslId($p['serviceid']);
}

function RealtimeRegisterSsl_FlashErrorStepOne() {
    $errors = \MGModule\RealtimeRegisterSsl\eServices\FlashService::getStepOneError();
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

if (isset($_POST['changeEmailModal'], $_SESSION['adminid']) AND $_POST['changeEmailModal'] === 'yes' && $_SESSION['adminid']) {
    $adminChangeApproverEmail = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminChangeApproverEmail($_POST);
    $adminChangeApproverEmail->run();
}

if (isset($_POST['action'], $_SESSION['adminid']) AND $_POST['action'] === 'getApprovalEmailsForDomain' && $_SESSION['adminid']) {
    
    try{
        $serviceid = $_REQUEST['id'];
        $ssl        = new MGModule\RealtimeRegisterSsl\eRepository\whmcs\service\SSL();
        $sslService = $ssl->getByServiceId($serviceid);

        $orderStatus = \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getOrderStatus($sslService->remoteid);
            
        
        if (!empty($orderStatus['domain'])) {
            $domain = $orderStatus['domain'];
        }
            
        if(!empty($orderStatus['product_id'])) {
            $apiRepo       = new \MGModule\RealtimeRegisterSsl\eRepository\RealtimeRegisterSsl\Products();
            $apiProduct    = $apiRepo->getProduct($orderStatus['product_id']);
            $brand = $apiProduct->brand;
        }

        $result = [
            'success' => 1,
            'domainEmails' => \MGModule\RealtimeRegisterSsl\eProviders\ApiProvider::getInstance()->getApi()->getDomainEmails($domain)
        ];
          
    } catch(Exception $ex)  {
        $result = [
            'success' => 0,
            'error' => $ex->getMessage()
        ];
    }
    
    ob_clean();
    echo json_encode($result);
    die();
}
if (isset($_POST['reissueModal'], $_SESSION['adminid']) AND $_POST['reissueModal'] === 'yes' AND $_SESSION['adminid'] ) {   
       
    $adminReissueCertificate = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminReissueCertificate($_POST);
    $adminReissueCertificate->run();   
}

if (isset($_POST['recheckModal'], $_SESSION['adminid']) AND $_POST['recheckModal'] === 'yes' AND $_SESSION['adminid']) {
    $adminCheckCertificateDetails = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminRecheckCertificateDetails($_POST);
    $adminCheckCertificateDetails->run();
}

if (isset($_POST['viewModal'], $_SESSION['adminid']) AND $_POST['viewModal'] === 'yes' AND $_SESSION['adminid']) {
    $adminViewCertyfifcate = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\AdminViewCertyfifcate($_POST);
    $adminViewCertyfifcate->run();
}

function RealtimeRegisterSsl_ClientAreaCustomReissueCertificate($params) {
    $clientReissueCertificate = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\ClientReissueCertificate($params, $_POST, $_GET);
    return $clientReissueCertificate->run();
}

function RealtimeRegisterSsl_ClientAreaCustomContactDetails($params) {
    $clientReissueCertificate = new \MGModule\RealtimeRegisterSsl\eServices\provisioning\ClientContactDetails($params, $_POST, $_GET);
    return $clientReissueCertificate->run();
}

function RealtimeRegisterSsl_ClientArea(array $params) {
    
    if(!empty($_REQUEST['json']))
    {
        header('Content-Type: text/plain');
        echo MGModule\RealtimeRegisterSsl\Server::getJSONClientAreaPage($params, $_REQUEST);
        die();
    }
    
    return \MGModule\RealtimeRegisterSsl\Server::getHTMLClientAreaPage($params, $_REQUEST);
}

function RealtimeRegisterSsl_ClientAreaCustomButtonArray() {
    $lang = \MGModule\RealtimeRegisterSsl\mgLibs\Lang::getInstance();
    return [
        $lang->T('Reissue Certificate') => 'ClientAreaCustomReissueCertificate'
    ];
}
add_hook('ClientAreaHeadOutput', 1, 'RealtimeRegisterSsl_SSLStepTwoJS');
add_hook('ClientAreaHeadOutput', 9999999999, 'RealtimeRegisterSsl_FlashErrorStepOne');
