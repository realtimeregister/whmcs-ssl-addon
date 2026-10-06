<?php

use WHMCS\Database\Capsule as DB;

if(!defined('DS')) {
    define('DS',DIRECTORY_SEPARATOR);
}

add_hook('AdminAreaFooterOutput', 1, function($vars)
{
if ($vars['filename'] == 'clientsservices' && $_GET['userid'] && $_GET['id']) {
    $hosting = DB::table('tblhosting')
    ->join('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid')
    ->where('tblhosting.id', $_GET['id'])
    ->first();
    if ($hosting->servertype != 'realtimeregister_ssl') {
        return;
    }
    return <<<JS

<script>
$(function(){
    $('#btnCreate').hide();
    
    var statushtrml = $('select[name="domainstatus"]').parent().html();
    var statustext = $('select[name="domainstatus"]').parent('.fieldarea').prev().text();

    $('#inputDedicatedip').parent('.fieldarea').prev().hide();
    $('#inputDedicatedip').parent('.fieldarea').hide();
    $('#inputUsername').parent('.fieldarea').prev().hide();
    $('#inputUsername').parent('.fieldarea').hide();
    $('#inputPassword').parent('.fieldarea').prev().hide();
    $('#inputPassword').parent('.fieldarea').hide();
    
    
    $('select[name="domainstatus"]').parent('.fieldarea').prev().hide();
    $('select[name="domainstatus"]').parent('.fieldarea').hide();
    $('select[name="domainstatus"]').remove();
    
    $('select[name="server"]').parent('.fieldarea').prev().html(statustext);
    $('select[name="server"]').parent('.fieldarea').html(statushtrml);
});
</script>

JS;
}
});

add_hook('ClientAreaHeadOutput', 1, function($vars) {
    if($vars['template'] == 'twenty-one' && $vars['module'] == 'realtimeregister_ssl') {
        return <<<HTML
<script type="text/javascript">
$(document).ready(function (){
    $('.modal-header.panel-heading').css({'display':'block'});
});
</script>
HTML;

    }
});
