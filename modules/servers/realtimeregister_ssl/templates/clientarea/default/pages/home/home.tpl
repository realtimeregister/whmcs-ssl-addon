<style>
    .sansTable {
        max-width: 100% !important;
        overflow-x:auto;
        border-collapse: collapse;
        border-style: hidden;
    }
    .sansTable th, .sansTable td {
    border: 1px solid #ddd;
    text-align: left;
    padding-left:8px;
    padding-right:2px;

    }
    #sansTd,
    .table {
        margin-bottom: 0 !important;
    }
    .modal .btn,
    #Action_Custom_Module_Button_Reissue_Certificate {
        margin: 2px !important;
    }
    #viewPrivateKey h4 {
        text-align: left !important;
    }
</style>
<script type="text/javascript" src="{$assetsURL}/js/addonLibs.js"></script>
{if $allOk === true}
    <table id="mainTable" class="table table-bordered">
        <colgroup>
            <col style="width: 20%"/>
            <col style="width: 80%"/>
        </colgroup>
        <tbody>
            {if $activationStatus === 'ACTIVE' || $activationStatus === 'COMPETED'}
                {if $configoption23}
                    <tr>
                        <td class="text-left">{$ADDONLANG->T('issued_ssl_message')}</td>
                        <td class="text-left">{$configoption23|nl2br}</td>
                    </tr>
                {/if}
            {/if}
            {if $activationStatus === 'processing' && $custom_guide} 
            <tr>
                <td class="text-left">{$ADDONLANG->T('custom_guide')}</td>
                <td class="text-left">{$custom_guide|nl2br}</td>
            </tr>
            {/if}
            {if $activationStatus === 'processing' && $configoption24}
            <tr>
                <td class="text-left">{$ADDONLANG->T('custom_guide')}</td>
                <td class="text-left">{$configoption24|nl2br}</td>
            </tr>
            {/if}
            <tr>
                <td class="text-left" >{$ADDONLANG->T('configurationStatus')}</td>
                <td class="text-left">{$ADDONLANG->T($configurationStatus)}{if $configurationStatus === 'Awaiting Configuration'} - <a href="{$configurationURL}">{$ADDONLANG->T('configureNow')}</a>{/if}</td>
            </tr>
            {if $activationStatus}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('activationStatus')}</td>
                    <td class="text-left">
                        {if $activationStatus === 'ACTIVE' || $activationStatus === 'COMPLETED'}
                            {$ADDONLANG->T('activationStatusActive')}
                        {elseif $activationStatus === 'new_order'}
                            {$ADDONLANG->T('activationStatusNewOrder')}
                        {elseif $activationStatus === 'pending'}
                            {$ADDONLANG->T('activationStatusPending')}
                        {elseif $activationStatus === 'cancelled'}
                            {$ADDONLANG->T('activationStatusCancelled')}
                        {elseif $activationStatus === 'payment needed'}
                            {$ADDONLANG->T('activationStatusPaymentNeeded')}
                        {elseif $activationStatus === 'processing'}
                            {$ADDONLANG->T('activationStatusProcessing')}
                        {elseif $activationStatus === 'incomplete'}
                            {$ADDONLANG->T('activationStatusIncomplete')}
                        {elseif $activationStatus === 'rejected'}
                            {$ADDONLANG->T('activationStatusRejected')}
                        {else}
                            {$activationStatus|ucfirst}
                        {/if}
                    </td>
                </tr>
            {/if}
            {if $activationStatus === 'ACTIVE' || $activationStatus === 'COMPLETED'}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('validFrom')}</td>
                    <td class="text-left">{$validFrom}</td>
                </tr>
                <tr>
                    <td class="text-left">{$ADDONLANG->T('validTill')}</td>
                    <td class="text-left">{$validTill}</td>
                </tr>
                {if $subscriptionEnds}
                    <tr>
                        <td class="text-left">{$ADDONLANG->T('subscriptionStarts')}</td>
                        <td class="text-left">{$subscriptionStarts}</td>
                    </tr>
                    <tr>
                        <td class="text-left">{$ADDONLANG->T('subscriptionEnds')}</td>
                        <td class="text-left">{$subscriptionEnds}</td>
                    </tr>
                    <tr>
                        <td class="text-left">{$ADDONLANG->T('nextReissue')}</td>
                        <td class="text-left"><strong>{$ADDONLANG->T('Reissue SSL within')} {$nextReissue} {$ADDONLANG->T('days')}</strong></td>
                    </tr>
                {else}
                    <tr>
                        <td class="text-left">{$ADDONLANG->T('nextRenewal')}</td>
                        <td class="text-left"><strong>{$ADDONLANG->T('Renew SSL within')} {$nextReissue} {$ADDONLANG->T('days')}</strong></td>
                    </tr>
                {/if}
            {/if}
            <!--{if $order_id}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('Order ID')}</td>
                    <td class="text-left">{$order_id}</td>
                </tr>
            {/if}
            -->
            {if $domain}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('domain')}</td>
                    <td class="text-left">{$domain}</td>
                </tr>
            {/if}
            {if $approver_email}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('Approver email')}</td>
                    <td class="text-left">{$approver_email}</td>
                </tr>
            {/if}
            {if $partner_order_id}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('Partner Order ID')}</td>
                    <td class="text-left">{$partner_order_id}</td>
                </tr>
            {/if}

            {if $approver_method}
                {if $dcv_method === 'http'}
                    <tr>
                        <td class="text-left">{$ADDONLANG->T('hashFile')}</td>
                        <td class="text-left" style="max-width:200px; word-wrap: break-word;">{$approver_method.$dcv_method.link}</td>
                    </tr>
                    <tr>
                        <td class="text-left">{$ADDONLANG->T('content')}</td>
                        <td class="text-left" style="max-width:200px; word-wrap: break-word;">{foreach $approver_method.$dcv_method.content as $content}{$content}<br />{/foreach}</td>
                    </tr>
                {else}
                    <tr id="validationData" >
                        {if $dcv_method === 'email'}
                            <td class="text-left">{$ADDONLANG->T('validationEmail')}</td>
                            <td class="text-left" >{$approver_method}</td>
                        {/if}
                        {if $dcv_method === 'dns'}
                            <td class="text-left ">{$ADDONLANG->T('dnsCnameRecord')}</td>
                            <td class="text-left" style="max-width:200px; word-wrap: break-word;">{$approver_method.dns.record|strtolower|replace:'cname':'CNAME'}</td>
                        {/if}
                    </tr>
                {/if}
            {/if}

            {if $sans}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('sans')}</td>
                    <td id="sansTd" colspan="2" class="text-left">
                            <table class="sansTable table table-bordered" >
                            <tbody>
                            {foreach $sans as $san}
                                <tr>
                                    <td colspan="2" class="text-center">{$ADDONLANG->T({$san.san_name})}</td>
                                </tr>
                                {if $san.method === 'http'}
                                    {if $activationStatus === 'processing' || $activationStatus === 'SUSPENDED'}
                                        <tr>
                                            <td style="width: 15%" class="text-left">{$ADDONLANG->T('hashFile')}</td>
                                            <td class="text-left" style="max-width:200px; word-wrap: break-word;">{$san.san_validation.link}</td>
                                        </tr>
                                        <tr>
                                            <td style="width: 15%" class="text-left">{$ADDONLANG->T('content')}</td>
                                            <td class="text-left" style="max-width:200px; word-wrap: break-word;">{foreach $san.san_validation.content as $content}{$content}<br />{/foreach}</td>
                                        </tr>
                                    {/if}
                               {else}
                                    {if $san.method === 'dns'}
                                        {if $activationStatus === 'processing' || $activationStatus === 'SUSPENDED'}
                                            <tr>
                                                <td style="width: 15%" class="text-left">{$ADDONLANG->T('dnsCnameRecord')}</td>
                                                <td class="text-left" style="max-width:200px; word-wrap: break-word;">{$san.san_validation}</td>
                                            </tr>
                                        {/if}
                                    {else}
                                        {if $san.san_validation != ''}
                                            {if $activationStatus === 'processing' || $activationStatus === 'SUSPENDED'}
                                                <tr>
                                                    <td style="width: 15%" class="text-left">{$ADDONLANG->T('validationEmail')}</td>
                                                    <td class="text-left" style="word-wrap: break-word;">{$san.san_validation}</td>
                                                </tr>
                                            {/if}
                                        {/if}
                                    {/if}
                                {/if}
                            {/foreach}
                            </tbody>
                        </table>
                    </td>
                </tr>
            {/if}
            {if $crt}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('crt')}</td>
                    <td class="text-left"><textarea onfocus="this.select()" rows="5" class="form-control">{$crt}</textarea></td>
                </tr>
            {/if}
            {if $ca}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('ca_chain')}</td>
                    <td class="text-left"><textarea onfocus="this.select()" rows="5" class="form-control">{$ca}</textarea></td>
                </tr>
            {/if}
            {if $csr}
                <tr>
                    <td class="text-left">{$ADDONLANG->T('csr')}</td>
                    <td class="text-left"><textarea onfocus="this.select()" rows="5" class="form-control">{$csr}</textarea></td>
                </tr>
            {/if}
            <tr id="additionalActionsTr">
                <td class="text-left">{$ADDONLANG->T('Actions')}</td>
                <td id="additionalActionsTd" class="text-left">
                    {if $visible_renew_button}
                    {if $displayRenewButton}
                        <button type="button" id="btnRenew" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('renew')}</button>
                    {/if}
                    {/if}
                    {if $activationStatus !== 'ACTIVE' && $activationStatus !== 'COMPLETED' && $dcv_method === 'email'}
                        <button type="button" id="resend-validation-email" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('resendValidationEmail')}</button>
                    {/if}
                    {if ($activationStatus === 'processing' || $activationStatus === 'SUSPENDED') && $btndownload}
                        <a href="{$btndownload}"><button type="button" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('download')}</button></a>
                    {/if}

                    {if $btnInstallCrt}
                        <button type="button" id="installCertificate" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('installCertificateBtn')}</button>
                    {/if}

                    {if $configurationStatus != 'Awaiting Configuration'}
                        {if $activationStatus === 'processing' || $activationStatus === 'SUSPENDED'}
                            <button type="button" id="btnRevalidate" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('domainvalidationmethod')}</button>
                        {elseif $activationStatus === 'ACTIVE' || $activationStatus === 'COMPLETED'}
                            <a class="btn btn-default" role="button" href="" id="Action_Custom_Module_Button_Reissue_Certificate">{$ADDONLANG->T('reissueCertificate')}</a>
                            <button type="button" id="send-certificate-email" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('sendCertificate')}</button>
                            {if $downloadca}<a href="{$downloadca}"><button type="button" id="download-ca" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('downloadca')}</button></a>{/if}
                            {if $downloadcrt}<a href="{$downloadcrt}"><button type="button" id="download-crt" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('downloadcrt')}</button></a>{/if}
                            {if $downloadcsr}<a href="{$downloadcsr}"><button type="button" id="download-csr" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('downloadcsr')}</button></a>{/if}
                            {if $downloadpem}<a href="{$downloadpem}"><button type="button" id="download-ca" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('downloadpem')}</button></a>{/if}
                        {/if}
                        {if $privateKey}
                            <button type="button" id="getPrivateKey" class="btn btn-default" style="margin:2px">{$ADDONLANG->T('getPrivateKeyBtn')}</button>
                        {/if}
                    {/if}
                </td>
            </tr>
        </tbody>
    </table>
    <script type="text/javascript">
        $(document).ready(function () {
            {if $activationStatus !== 'ACTIVE' && $activationStatus !== 'COMPLETED'}
                //$('#Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reissue_Certificate').remove();
            {else}
                $('#resend-validation-email').remove();
                $('#btnChange_Approver_Email').remove();
            {/if}
            var reissueUrl= $('#Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reissue_Certificate').attr('href');
            $('#Action_Custom_Module_Button_Reissue_Certificate').prop('href', reissueUrl);
            $('#Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reissue_Certificate').remove();
        });
    </script>

    <!--RENEW MODAL-->
    <div class="modal fade" id="modalRenew" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content panel panel-primary">
                <div class="modal-header panel-heading">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">{$ADDONLANG->T('Close')}</span>
                    </button>
                    <h4 class="modal-title">{$ADDONLANG->T('renewModalTitle')}</h4>
                </div>
                <div class="modal-body panel-body" id="modalRenewBody">

                    <div class="alert alert-success hidden" id="modalRenewSuccess">
                        <strong>Success!</strong> <span></span>
                    </div>
                    <div class="alert alert-danger hidden" id="modalRenewDanger">
                        <strong>Error!</strong> <span></span>
                    </div>
                    <form class="form-horizontal" role="form" id="modalRenewForm">
                            <div class="col-sm-12" style="padding: 25px;">
                                {$ADDONLANG->T('renewModalConfirmInformation')}
                            </div>
                    </form>
                </div>
                <div class="modal-footer panel-footer">
                    <button type="button" id="modalRenewSubmit" class="btn btn-primary">
                        {$ADDONLANG->T('Submit')}
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        {$ADDONLANG->T('Close')}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function () {

            var serviceUrl = 'clientarea.php?action=productdetails&id={$serviceid}&json=1',
                    renewBtn = $('#btnRenew'),
                    renewForm,
                    renewModal,
                    renewBody,
                    renewInput,
                    renewDangerAlert,
                    renewSuccessAlert,
                    renewSubmitBtn,
                    body = $('body');

            function assignModalElements(init) {
                renewModal = $('#modalRenew');
                renewBody = $('#modalRenewBody');

                if (init) {
                    renewBody.contents()
                    .filter(function(){
                        return this.nodeType === 8;
                    })
                    .replaceWith(function(){
                        return this.data;
                    });
                }

                if (!init) {
                    renewForm = $('#modalRenewForm');
                    renewSubmitBtn = $('#modalRenewSubmit');
                    renewBody = $('#modalRenewBody');
                    renewDangerAlert = $('#modalRenewDanger');
                    renewSuccessAlert = $('#modalRenewSuccess');
                }
            }

            function moveModalToBody() {

                body.append(renewModal.clone());
                assignModalElements(false);
                renewModal.remove();
            }

            function unbindOnClickForrenewBtn() {
                renewBtn.attr('onclick', '');
            }

            function bindModalFrorenewBtn() {
                renewBtn.off().on('click', function () {
                    renewModal.modal('show');
                    show(renewSubmitBtn);
                    show(renewForm);
                    hideAll();
                });
            }

            function bindSubmitBtn() {
                renewSubmitBtn.off().on('click', function () {
                    submitrenewModal();
                });
            }

            function showSuccessAlert(msg) {
                var reloadInfo = '{$ADDONLANG->T('redirectToInvoiceInformation')}'
                show(renewSuccessAlert);
                hide(renewDangerAlert);
                renewSuccessAlert.children('span').html(msg + ' ' + reloadInfo);
            }

            function showDangerAlert(msg) {
                hide(renewSuccessAlert);
                show(renewDangerAlert);
                renewDangerAlert.children('span').html(msg);
            }

            function addSpinner(element) {
                element.append('<i class="fa fa-spinner fa-spin"></i>');
            }

            function removeSpinner(element) {
                element.find('.fa-spinner').remove();
            }

            function show(element) {
                element.removeClass('hidden');
            }

            function hide(element) {
                element.addClass('hidden');
            }

            function enable(element) {
                element.removeAttr('disabled')
                element.removeClass('disabled');
            }

            function disable(element) {
                element.attr("disabled", true);
                element.addClass('disabled');
            }

            function hideAll() {
                hide(renewDangerAlert);
                hide(renewSuccessAlert);
            }

            function anErrorOccurred() {
                showDangerAlert('{$ADDONLANG->T('anErrorOccurred')}');
            }

            function isJsonString(str) {
                try {
                    JSON.parse(str);
                } catch (e) {
                    return false;
                }
                return true;
            }

            function resize(element) {
                element.css('height', "");
            }

            function submitrenewModal() {
                addSpinner(renewSubmitBtn);
                disable(renewSubmitBtn);

                var data = {
                    renewModal: 'yes',
                    serviceId: {$serviceid},
                    userID: {$userid},
                    'addon-action': 'renew'
                };
                $.ajax({
                    url: serviceUrl,
                    data: data,
                    json: 1,
                    success: function (ret) {
                        var data;
                        ret = ret.replace("<JSONRESPONSE#", "");
                        ret = ret.replace("#ENDJSONRESPONSE>", "");
                        if (!isJsonString(ret)) {
                            anErrorOccurred();
                            return;
                        }
                        data = JSON.parse(ret);
                        if (data.success === 1 || data.success === true) {
                            showSuccessAlert(data.data.msg);
                            hide(renewSubmitBtn);
                            resize(renewBody);
                            hide(renewForm);
                            window.setTimeout(function(){ window.location.replace('viewinvoice.php?id=' + data.data.invoiceID) }, 5000);
                        } else {
                            if(typeof data.data.invoiceID !== 'undefined')
                            {
                                var reloadInfo = '{$ADDONLANG->T('redirectToInvoiceInformation')}'
                                showDangerAlert(data.error + ' ' + reloadInfo);
                                window.setTimeout(function(){ window.location.replace('viewinvoice.php?id=' + data.data.invoiceID) }, 5000);
                            } else {
                                showDangerAlert(data.error);
                            }
                        }
                    },
                    error: function (jqXHR, errorText, errorThrown) {
                        anErrorOccurred();
                    },
                    complete: function () {
                        removeSpinner(renewSubmitBtn);
                        enable(renewSubmitBtn);
                    }
                });
            }

            assignModalElements(true);
            moveModalToBody();
            renewForm.trigger("reset");
            unbindOnClickForrenewBtn();
            bindModalFrorenewBtn();
            bindSubmitBtn();
        });
    </script>
    <!--END RENEW MODAL-->
    <div class="modal fade" id="modalRevalidate" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content panel panel-primary">
                <div class="modal-header panel-heading">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">{$ADDONLANG->T('Close')}</span>
                    </button>
                    <h4 class="modal-title">{$ADDONLANG->T('revalidateModalTitle')}</h4>
                </div>
                <div {if $sans}style="overflow-y: auto; height:{if $sans|@count == 1 }200{elseif $sans|@count == 2}275{else}350{/if}px;"{/if} class="modal-body panel-body" id="modalRevalidateBody">

                    <div class="alert alert-success hidden" id="modalRevalidateSuccess">
                        <strong>Success!</strong> <span></span>
                    </div>
                    <div class="alert alert-danger hidden" id="modalRevalidateDanger">
                        <strong>Error!</strong> <span></span>
                    </div>
                    <form class="form-horizontal" role="form" id="modalRevalidateForm">
                            <div class="col-sm-12">
                                <table class="table revalidateTable">
                                    <thead>
                                        <tr>
                                            <th>{$ADDONLANG->T('revalidateModalDomainLabel')}</th>
                                            <th style="width:35%;">{$ADDONLANG->T('revalidateModalMethodLabel')}</th>
                                            <th> {if 'email'|in_array:$disabledValidationMethods} {else}{$ADDONLANG->T('revalidateModalEmailLabel')}{/if}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{$domain}</td>
                                            <td>
                                                <div class="form-group">
                                                    <select style="width:70%;" type="text" name="newDcvMethod_0" class="form-control modalRevalidateInput" >
                                                        <option value="" selected>{$ADDONLANG->T('pleaseChooseOne')}</option>
                                                        {if !'email'|in_array:$disabledValidationMethods}
                                                            <option value="email">{$ADDONLANG->T('revalidateModalMethodEmail')}</option>
                                                        {/if}
                                                        {if !'http'|in_array:$disabledValidationMethods}
                                                            <option value="http">{$ADDONLANG->T('revalidateModalMethodHttp')}</option>
                                                        {/if}
                                                        {if !'dns'|in_array:$disabledValidationMethods}
                                                        <option value="dns">{$ADDONLANG->T('revalidateModalMethodDns')}</option>
                                                        {/if}
                                                        
                                                    </select>
                                                </div>
                                            </td>
                                            <td>
                                                <div style="display:none;" class="form-group newApproverEmailFormGroup_0">
                                                    <select type="text" name="newApproverEmailInput_0"class="form-control newApproverEmailInputValidation"/>
                                                        <option id="loadingDomainEmails">{$ADDONLANG->T('loading')}</option>
                                                    </select>
                                                </div>
                                            </td>
                                        </tr>
                                        {*if $sans && !$brand|in_array:$brandsWithOnlyEmailValidation*}
                                            {$i = 1}
                                            {foreach $sans as $san}
                                                <tr>
                                                    {if $brand == 'digicert' || $brand == 'thawte' || $brand == 'rapidssl'}
                                                        <td>{$san.san_name}</td>
                                                        <td></td>
                                                        <td></td>
                                                    {else}
                                                        <td>{$san.san_name}</td>
                                                        <td>
                                                            <div class="form-group">
                                                                <select style="width:70%;" type="text" name="newDcvMethod_{$i}" class="form-control modalRevalidateInput">
                                                                    <option value="" selected>{$ADDONLANG->T('pleaseChooseOne')}</option>
                                                                    {if !'email'|in_array:$disabledValidationMethods}
                                                                        <option value="email">{$ADDONLANG->T('revalidateModalMethodEmail')}</option>
                                                                    {/if}
                                                                    {if !'http'|in_array:$disabledValidationMethods}
                                                                        <option value="http">{$ADDONLANG->T('revalidateModalMethodHttp')}</option>
                                                                    {/if}
                                                                    {if !'dns'|in_array:$disabledValidationMethods}
                                                                        <option value="dns">{$ADDONLANG->T('revalidateModalMethodDns')}</option>
                                                                    {/if}
                                                                </select>
                                                            </div>
                                                        <td>
                                                            <div style="display:none;" class="form-group newApproverEmailFormGroup_{$i}">
                                                                <select type="text" name="newApproverEmailInput_{$i}" class="form-control newApproverEmailInputValidation"/>
                                                                    <option id="loadingDomainEmails">{$ADDONLANG->T('loading')}</option>
                                                                </select>
                                                            </div>
                                                        </td>
                                                    {/if}
                                                </tr>
                                            {$i=$i+1}
                                            {/foreach}
                                        {*/if*}
                                    </tbody>
                                </table>
                            </div>
                    </form>
                </div>
                <div class="modal-footer panel-footer">
                    <button type="button" id="modalRevalidateSubmit" class="btn btn-primary">
                        {$ADDONLANG->T('Submit')}
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        {$ADDONLANG->T('Close')}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function () {
            
            var wildcard = false;
            
            $('.revalidateTable tbody tr').each(function() {
                var string = $(this).find('td:first-child').text();
                var substring = '*.';
                if(string.indexOf(substring) !== -1)
                {
                    wildcard = true;
                }
            });

            $('.revalidateTable tbody tr').each(function() {
                var string = $(this).find('td:first-child').text();
                var substring = '*.';
                if(string.indexOf(substring) !== -1)
                {
                    $(this).find('option[value="http"]').remove();
                }
            });
            var serviceUrl = 'clientarea.php?action=productdetails&id={$serviceid}&json=1',
                    revalidateBtn = $('#btnRevalidate'),
                    revalidateForm,
                    revalidateModal,
                    revalidateBody,
                    revalidateInput,
                    revalidateDangerAlert,
                    revalidateSuccessAlert,
                    revalidateSubmitBtn,
                    body = $('body');

            function assignModalElements(init) {
                revalidateModal = $('#modalRevalidate');
                revalidateBody = $('#modalRevalidateBody');

                if (init) {
                    revalidateBody.contents()
                    .filter(function(){
                        return this.nodeType === 8;
                    })
                    .replaceWith(function(){
                        return this.data;
                    });
                }

                if (!init) {
                    revalidateForm = $('#modalRevalidateForm');
                    revalidateSubmitBtn = $('#modalRevalidateSubmit');
                    revalidateInput = $('.modalRevalidateInput');
                    revalidateBody = $('#modalRevalidateBody');
                    revalidateEmail = $('.newApproverEmailInputValidation');
                    revalidateDangerAlert = $('#modalRevalidateDanger');
                    revalidateSuccessAlert = $('#modalRevalidateSuccess');
                }
            }

            function moveModalToBody() {

                body.append(revalidateModal.clone());
                assignModalElements(false);
                revalidateModal.remove();
            }

            function unbindOnClickForrevalidateBtn() {
                revalidateBtn.attr('onclick', '');
            }

            function bindModalFrorevalidateBtn() {
                revalidateBtn.off().on('click', function () {
                    revalidateModal.modal('show');
                    show(revalidateSubmitBtn);
                    show(revalidateForm);
                    hideAll();
                });
            }

            function bindSubmitBtn() {
                revalidateSubmitBtn.off().on('click', function () {
                    submitrevalidateModal();
                });
            }

            function showSuccessAlert(msg) {
                var reloadInfo = '{$ADDONLANG->T('reloadInformation')}'
                show(revalidateSuccessAlert);
                hide(revalidateDangerAlert);
                revalidateSuccessAlert.children('span').html(msg + ' ' + reloadInfo);
            }

            function showDangerAlert(msg) {
                hide(revalidateSuccessAlert);
                show(revalidateDangerAlert);
                revalidateDangerAlert.children('span').html(msg);
            }

            function addSpiner(element) {
                element.append('<i class="fa fa-spinner fa-spin"></i>');
            }

            function removeSpiner(element) {
                element.find('.fa-spinner').remove();
            }

            function show(element) {
                element.removeClass('hidden');
            }

            function hide(element) {
                element.addClass('hidden');
            }

            function enable(element) {
                element.removeAttr('disabled')
                element.removeClass('disabled');
            }

            function disable(element) {
                element.attr("disabled", true);
                element.addClass('disabled');
            }

            function hideAll() {
                hide(revalidateDangerAlert);
                hide(revalidateSuccessAlert);
            }

            function anErrorOccurred() {
                showDangerAlert('{$ADDONLANG->T('anErrorOccurred')}');
            }

            function isJsonString(str) {
                try {
                    JSON.parse(str);
                } catch (e) {
                    return false;
                }
                return true;
            }

            function resize(element) {
                element.css('height', "");
            }

            function submitrevalidateModal() {
                addSpiner(revalidateSubmitBtn);
                disable(revalidateSubmitBtn);
                var newMethods = {};
                var newdomains = {};
                
                $('.revalidateTable tbody tr').each(function(key,value){
                    var domaintemp = $(this).find('td:first-child').text();
                    domaintemp = domaintemp.replace("*", "___");
                    newdomains[domaintemp] = domaintemp;
                });
                
                
                revalidateInput.each(function(key,value){
                    var node = $('.revalidateTable>tbody').find('tr:eq('+key+')').find('td:eq(0)')[1];
                    if(typeof node !== 'undefined') {
                        domain = node.textContent;
                    }
                    domain = domain.replace("*", "___");
                    if(this.value === 'email') {
                        if(key === 0) {
                            newMethods[domain] = $('select[name="newApproverEmailInput_'+key+'"]')[2].value;
                        } else {
                            newMethods[domain] = $('select[name="newApproverEmailInput_'+key+'"]')[1].value;
                        }
                    } else {
                        if(this.value !== "") {
                            newMethods[domain] = this.value;
                        }
                    }
                    
                });
                if(jQuery.isEmptyObject(newMethods)) {
                    showDangerAlert('{$ADDONLANG->T('noValidationMethodSelected')}');
                    removeSpiner(revalidateSubmitBtn);
                    enable(revalidateSubmitBtn);
                    return;
                }
                var noEmailError = '';
                $.each(newMethods,function(key, value){
                    if(value === '{$ADDONLANG->T('pleaseChooseOne')}' || value === '{$ADDONLANG->T('loading')}') {
                        noEmailError = '{$ADDONLANG->T('noEmailSelectedForDomain')}' + key.replace("___", "*");
                        return true;
                    }
                });
                if(noEmailError !== '') {
                    showDangerAlert(noEmailError);
                    removeSpiner(revalidateSubmitBtn);
                    enable(revalidateSubmitBtn);
                    return;
                }
                var data = {
                    revalidateModal: 'yes',
                    newDcvMethods: newMethods,
                    newdomains: newdomains,
                    serviceId: {$serviceid},
                    userID: {$userid},
                    brand: '{$brand}',
                    'addon-action': 'revalidate'
                };
                $.ajax({
                    url: serviceUrl,
                    data: data,
                    json: 1,
                    success: function (ret) {
                        var data;
                        ret = ret.replace("<JSONRESPONSE#", "");
                        ret = ret.replace("#ENDJSONRESPONSE>", "");
                        if (!isJsonString(ret)) {
                            anErrorOccurred();
                            return;
                        }
                        data = JSON.parse(ret);
                        if (data.success === 1 || data.success === true) {
                            showSuccessAlert(data.data.msg);
                            revalidateInput.val('');
                            hide(revalidateSubmitBtn);
                            resize(revalidateBody);
                            hide(revalidateForm);
                            window.setTimeout(function(){ location.reload() }, 5000);
                        } else {
                            showDangerAlert(data.data.msg);
                        }
                    },
                    error: function (jqXHR, errorText, errorThrown) {
                        anErrorOccurred();
                    },
                    complete: function () {
                        removeSpiner(revalidateSubmitBtn);
                        enable(revalidateSubmitBtn);
                    }
                });
            }

            assignModalElements(true);
            moveModalToBody();
            revalidateForm.trigger("reset");
            unbindOnClickForrevalidateBtn();
            bindModalFrorevalidateBtn();
            bindSubmitBtn();
            revalidateInput.on("change", function() {
                    var fieldIndex = this.name.replace('newDcvMethod_', '');
                    var domain = $(this).closest('td').prev('td').text();
                    var selectedMethod = '';
                    selectedMethod = $(this).find(":selected").val();
                    if(selectedMethod === 'email') {
                        $(".newApproverEmailFormGroup_"+fieldIndex).css('display', 'block');
                        getDomainEmails(null, domain, fieldIndex);
                    } else {
                        $(".newApproverEmailFormGroup_"+fieldIndex).css('display', 'none');
                    }
            });
        });
    </script>
    <div class="modal fade" id="modalChangeApprovedEmail" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content panel panel-primary">
                <div class="modal-header panel-heading">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">Close</span>
                    </button>
                    <h4 class="modal-title">{$ADDONLANG->T('changeApproverEmailModalModalTitle')}</h4>
                </div>
                <div class="modal-body panel-body" id="modalChangeApprovedEmailBody">
                    <div class="alert alert-success hidden" id="modalChangeApprovedEmailSuccess">
                        <strong>Success!</strong> <span></span>
                    </div>
                    <div class="alert alert-danger hidden" id="modalChangeApprovedEmailDanger">
                        <strong>Error!</strong> <span></span>
                    </div>
                    <div class="form-group newApproverEmailFormGroup">
                        <label class="col-sm-3 control-label">{$ADDONLANG->T('newApproverEmailModalModalLabel')}</label>
                        <div class="col-sm-9">
                            <select type="text" name="newApproverEmailInput_0" id="modalChangeApprovedEmailInput" class="form-control"/>
                                <option id="loadingDomainEmails">{$ADDONLANG->T('loading')}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer panel-footer">
                    <button type="button" id="modalChangeApprovedEmailSubmit" class="btn btn-primary">
                        {$ADDONLANG->T('Submit')}
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        {$ADDONLANG->T('Close')}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        $(document).ready(function () {
            var serviceUrl = 'clientarea.php?action=productdetails&id={$serviceid}',
                    changeEmailBtn = $('#btnChange_Approver_Email'),
                    changeEmailForm,
                    changeEmailModal,
                    changeEmailBody,
                    changeEmailInput,
                    changeEmailDangerAlert,
                    changeEmailSuccessAlert,
                    changeEmailSubmitBtn,
                    body = $('body');
            function assignModalElements(init) {
                changeEmailModal = $('#modalChangeApprovedEmail');
                changeEmailBody = $('#modalChangeApprovedEmailBody');

                if (init) {
                    changeEmailBody.contents()
                    .filter(function(){
                        return this.nodeType === 8;
                    })
                    .replaceWith(function(){
                        return this.data;
                    });
                }

                if (!init) {
                    changeEmailForm = $('.newApproverEmailFormGroup');
                    changeEmailSubmitBtn = $('#modalChangeApprovedEmailSubmit');
                    changeEmailInput = $('#modalChangeApprovedEmailInput');
                    changeEmailDangerAlert = $('#modalChangeApprovedEmailDanger');
                    changeEmailSuccessAlert = $('#modalChangeApprovedEmailSuccess');
                }
            }

            function moveModalToBody() {
                body.append(changeEmailModal.clone());
                assignModalElements(false);

                changeEmailModal.remove();
            }

            function unbindOnClickForChangeEmailBtn() {
                changeEmailBtn.attr('onclick', '');
            }

            function bindModalFroChangeEmailBtn() {
                changeEmailBtn.off().on('click', function () {
                    changeEmailModal.modal('show');
                    show(changeEmailSubmitBtn);
                    show(changeEmailForm);
                    hideAll();
                });
            }

            function bindSubmitBtn() {
                changeEmailSubmitBtn.off().on('click', function () {
                    submitChangeEmailModal();
                });
            }

            function showSuccessAlert(msg) {
                var reloadInfo = '{$ADDONLANG->T('reloadInformation')}'
                show(changeEmailSuccessAlert);
                hide(changeEmailDangerAlert);
                changeEmailSuccessAlert.children('span').html(msg + ' ' + reloadInfo);
            }

            function showDangerAlert(msg) {
                hide(changeEmailSuccessAlert);
                show(changeEmailDangerAlert);
                changeEmailDangerAlert.children('span').html(msg);
            }

            function addSpiner(element) {
                element.append('<i class="fa fa-spinner fa-spin"></i>');
            }

            function removeSpiner(element) {
                element.find('.fa-spinner').remove();
            }

            function show(element) {
                element.removeClass('hidden');
            }

            function hide(element) {
                element.addClass('hidden');
            }

            function enable(element) {
                element.removeAttr('disabled')
                element.removeClass('disabled');
            }

            function disable(element) {
                element.attr("disabled", true);
                element.addClass('disabled');
            }

            function hideAll() {
                hide(changeEmailDangerAlert);
                hide(changeEmailSuccessAlert);
            }

            function anErrorOccurred() {
                showDangerAlert('{$ADDONLANG->T('anErrorOccurred')}');
            }

            function isJsonString(str) {
                try {
                    JSON.parse(str);
                } catch (e) {
                    return false;
                }
                return true;
            }

            function submitChangeEmailModal() {
                addSpiner(changeEmailSubmitBtn);
                disable(changeEmailSubmitBtn);

                var data = {
                    newEmail: changeEmailInput.val(),
                    serviceId: {$serviceid},
                    userID: {$userid},
                    json: 1,
                    'addon-action': 'changeApproverEmail'
                };
                $.ajax({
                    type: "POST",
                    url: serviceUrl,
                    data: data,
                    success: function (ret) {
                        var data;
                        ret = ret.replace("<JSONRESPONSE#", "");
                        ret = ret.replace("#ENDJSONRESPONSE>", "");
                        if (!isJsonString(ret)) {
                            anErrorOccurred();
                            return;
                        }
                        data = JSON.parse(ret);
                        if (data.success) {
                            showSuccessAlert(data.data.msg);
                            changeEmailInput.val('');
                            hide(changeEmailSubmitBtn);
                            hide(changeEmailForm);
                            window.setTimeout(function(){ location.reload() }, 5000);
                        } else {
                            showDangerAlert(data.error);
                        }
                    },
                    error: function (jqXHR, errorText, errorThrown) {
                        anErrorOccurred();
                    },
                    complete: function () {
                        removeSpiner(changeEmailSubmitBtn);
                        enable(changeEmailSubmitBtn);
                    }
                });
            }

            assignModalElements(true);
            moveModalToBody();
            unbindOnClickForChangeEmailBtn();
            bindModalFroChangeEmailBtn();
            bindSubmitBtn();
        });
    </script>
{/if}
    <div class="modal fade" id="viewPrivateKey" role="dialog" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content panel panel-primary">
                <div class="modal-header panel-heading">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only">Close</span>
                    </button>
                    <h4 class="modal-title">{$ADDONLANG->T('viewPrivateKeyModalTitle')}</h4>
                </div>
                <div class="modal-body panel-body" id="modalViewPrivateKey">
                     <div class="form-group">
                        <textarea id="privateKey" class="form-control"  rows="13" style="overflow:auto;resize:none"></textarea>
                     </div>
                </div>
                <div class="modal-footer panel-footer">
                    <button type="button" class="btn btn-default download-private-key">
                        {$ADDONLANG->T('downloadToFile')}
                    </button>
                    <button type="button" class="btn btn-default copy-to-clipboard">
                        {$ADDONLANG->T('copyToClipboard')}
                    </button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        {$ADDONLANG->T('Close')}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">

        // TODO: Refactor this mess


            function getDomainEmails(serviceid = null, domain, index){
                var brand = '{$brand}'
                var serviceUrl = 'clientarea.php?action=productdetails&json=1&addon-action=getApprovalEmailsForDomain&brand=' + brand + '&domain=' + domain;

                serviceUrl += '&id=' + '{$serviceid}'

                $.ajax({
                        type: "POST",
                        url: serviceUrl,
                        success: function (ret) {
                            var data;
                            $('select[name="newApproverEmailInput_'+index+'"]').empty();
                            ret = ret.replace("<JSONRESPONSE#", "");
                            ret = ret.replace("#ENDJSONRESPONSE>", "");

                            data = JSON.parse(ret);
                            if (data.success === 1) {
                                var  htmlOptions = [];
                                htmlOptions += '<option>'+'{$ADDONLANG->T('pleaseChooseOne')}'+'</option>';
                                var domainEmails = data.data.domainEmails;
                                for (var i = 0; i < domainEmails.length; i++) {
                                     htmlOptions += '<option value="' + domainEmails[i] + '">' + domainEmails[i] + '</option>';
                                }

                                $('select[name="newApproverEmailInput_'+index+'"]').append(htmlOptions);
                            } else {
                                showDangerAlert(data.msg);
                            }
                        },
                        error: function (jqXHR, errorText, errorThrown) {
                            anErrorOccurred();
                        }
                    });
            }
            $(function() {
                $('.download-private-key').on('click', () => {
                    const key = $('#privateKey').text();
                    const blob = new Blob([key], { type: "text/plain" });

                    const a = document.createElement("a");
                    a.href = URL.createObjectURL(blob);
                    a.download = "private.key";

                    document.body.appendChild(a);
                    a.click();

                    document.body.removeChild(a);
                    URL.revokeObjectURL(a.href);
                })

                $('.copy-to-clipboard').on('click', () => {
                    const key = $('#privateKey').text();
                    navigator.clipboard.writeText(key)
                })

                {literal}
                var serviceid = {/literal}'{$serviceid}'{literal};
                var domain =   {/literal}'{$domain}'{literal};
                jQuery('#btnChange_Approver_Email').on("click", function(){
                    getDomainEmails(serviceid, domain, 0);
                });
                var additionalActions = $('#additionalActionsTd').html().trim();
                if(additionalActions.length == 0) {
                    $('#additionalActionsTr').remove();
                }
                jQuery('#resend-validation-email').on("click",function(){
                    $('#resend-validation-email').append(' <i id="resendSpinner" class="fa fa-spinner fa-spin"></i>');
                    JSONParser.request('resendValidationEmail',{json: 1, id: serviceid}, function (data) {
                        if (data.success == true) {
                            $('#AddonAlerts>div[data-prototype="success"]').show();
                            $('#AddonAlerts>div[data-prototype="success"] strong').html(data.message);
                        } else if (data.success == false) {
                            $('#AddonAlerts>div[data-prototype="error"]').show();
                            $('#AddonAlerts>div[data-prototype="error"] strong').html(data.message);
                        }
                        $('#resend-validation-email').find('.fa-spinner').remove();
                    }, false);
                });
                jQuery('#send-certificate-email').on("click",function(){
                    $('#send-certificate-email').find('.fa-spinner').remove();
                    $('#send-certificate-email').append(' <i id="resendSpinner" class="fa fa-spinner fa-spin"></i>');
                    JSONParser.request('sendCertificateEmail',{json: 1, id: serviceid}, function (data) {
                        if (data.success == true) {
                            $('#AddonAlerts>div[data-prototype="success"]').show();
                            $('#AddonAlerts>div[data-prototype="success"] strong').html(data.message);
                        } else if (data.success == false) {
                            $('#AddonAlerts>div[data-prototype="error"]').show();
                            $('#AddonAlerts>div[data-prototype="error"] strong').html(data.message);
                        }
                        $('#send-certificate-email').find('.fa-spinner').remove();
                    }, false);
                });
                jQuery('#getPrivateKey').on("click",function(){

                    $('#getPrivateKey').append(' <i class="fa fa-spinner fa-spin"></i>');
                    JSONParser.request('getPrivateKey',{json: 1,id: serviceid}, function (data) {
                        if (data.success == true) {
                            $('#AddonAlerts>div').css('display', 'none');
                            $('#getPrivateKey').find('.fa-spinner').remove();
                            $('#viewPrivateKey').modal('toggle');
                            $('#privateKey').text(data.privateKey);
                        } else if (data.success == false) {
                            $('#getPrivateKey').find('.fa-spinner').remove();
                            $('#AddonAlerts>div[data-prototype="error"]').show();
                            $('#AddonAlerts>div[data-prototype="error"] strong').html(data.message);
                        }
                    }, false);
                });

                jQuery('#installCertificate').on("click",function(){

                    $('#installCertificate').append(' <i class="fa fa-spinner fa-spin"></i>');
                    JSONParser.request('installCertificate',{json: 1,id: serviceid}, function (data) {
                        if (data.success == true) {
                            $('#AddonAlerts>div').css('display', 'none');
                            $('#installCertificate').find('.fa-spinner').remove();
                            $('#AddonAlerts>div[data-prototype="success"]').show();
                            $('#AddonAlerts>div[data-prototype="success"] strong').html(data.message);
                        } else if (data.success == false) {
                            $('#installCertificate').find('.fa-spinner').remove();
                            $('#AddonAlerts>div[data-prototype="error"]').show();
                            $('#AddonAlerts>div[data-prototype="error"] strong').html(data.message);
                        }
                    }, false);
                });

                jQuery('#reissue-order').on("click",function(){
                    JSONParser.request('reIssueOrder',{json: 1}, function (data) {
                        if (data.success == true) {
                            $('#AddonAlerts>div[data-prototype="success"]').show();
                            $('#AddonAlerts>div[data-prototype="success"] strong').html(data.message);
                        } else if (data.success == false) {
                            $('#AddonAlerts>div[data-prototype="error"]').show();
                            $('#AddonAlerts>div[data-prototype="error"] strong').html(data.message);
                        }
                    }, false);
                });

                //for template simplicity modal header bug
                var color = $('#modalRevalidate').find('.panel-heading').css('background-color');
                $('#viewPrivateKey').find('.panel-heading').css('background-color', color);
            });
        {/literal}
    </script>

<div class="modal fade" id="modalRecheck" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content panel panel-primary" style="width:900px;left:-25%;">
            <div class="modal-header panel-heading">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title" id="ModuleSuspendLabel">Check Certificate Details</h4>
            </div>
            <div class="modal-body panel-body" id="modalRecheckBody">
                <div class="alert alert-success hidden" id="modalRecheckSuccessAlert">
                    <strong>Success!</strong> <span></span>
                </div>
                <div class="alert alert-danger hidden" id="modalRecheckDangerAlert">
                    <strong>Error!</strong> <span></span>
                </div>
                <div class="text-center hidden" id="modalRecheckLoading">
                    Loading...
                </div>
                <div id="modalRecheckDetails">
                    <table id="certificate_details" class="table" style="width:100%;text-align:center;">
                        <colgroup>
                            <col width="40%"/>
                            <col width="60%"/>
                        </colgroup>
                        <tr id="configuration_status">
                            <td class="text-left" >{$ADDONLANG->T('configurationStatus')}</td>
                            <td class="text-left"></td>
                        </tr>
                        <tr id="order_status">
                            <td class="text-left">{$ADDONLANG->T('activationStatus')}</td>
                            <td class="text-left"></td>
                        </tr>
                        <tr id="valid_from">
                            <td class="text-left">{$ADDONLANG->T('validFrom')}</td>
                            <td class="text-left"></td>
                        </tr>
                        <tr id="valid_till">
                            <td class="text-left">{$ADDONLANG->T('validTill')}</td>
                            <td class="text-left"></td>
                        </tr>
                        <tr id="domain">
                            <td class="text-left">{$ADDONLANG->T('domain')}</td>
                            <td class="text-left"></td>
                        </tr>
                        <tr id="partner_order_id">
                            <td class="text-left">{$ADDONLANG->T('Partner Order ID')}</td>
                            <td class="text-left"></td>
                        </tr>
                        <tr id="sans">
                            <td class="text-left">{$ADDONLANG->T('sans')}</td>
                            <td id="sansTd" colspan="2" class="text-left">
                                <table class="sansTable table table-bordered" >

                                </table>
                            </td>
                        </tr>
                        <tr id="crt">
                            <td class="text-left">{$ADDONLANG->T('crt')}</td>
                            <td class="text-left"><textarea onfocus="this.select()" rows="5" class="form-control"></textarea></td>
                        </tr>
                        <tr id="ca">
                            <td class="text-left">{$ADDONLANG->T('ca_chain')}</td>
                            <td class="text-left"><textarea onfocus="this.select()" rows="5" class="form-control"></textarea></td>
                        </tr>
                        <tr id="csr">
                            <td class="text-left">{$ADDONLANG->T('csr')}</td>
                            <td class="text-left"><textarea onfocus="this.select()" rows="5" class="form-control"></textarea></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="modal-footer panel-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
