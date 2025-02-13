
<script type="text/javascript">
    $(document).ready(function () {
        var countries = {$countries};
        var countryOptions = '';
        for (var key in countries) {
            countryOptions += '<option value="' + key + '">'+ countries[key] + '</option>'
        }
        //for control template
        if($('#internal-content form').length > 0) {
            var element = $('#internal-content form');
        }
        //for six template
        else if($('.main-content form').length > 0) {
            var element = $('.main-content form');
        }
        else if($('#main-body .primary-content form').length > 0) {
            var element = $('#main-body .primary-content form');
        }
        //for flare template
        else if($('#main-body form').length > 0) {
            var element = $('#main-body form');
        }

        element.append('\
                        <div class="modal fade" id="modalGenerateCsr" role="dialog" aria-hidden="true">\n\
                            <div class="modal-dialog">\n\
                                <div class="modal-content panel panel-primary">\n\
                                    <div class="modal-header panel-heading" style="display:block;">\n\
                                        <button type="button" class="close" data-dismiss="modal">\n\
                                            <span aria-hidden="true">&times;</span>\n\
                                            <span class="sr-only">Close</span>\n\
                                        </button>\n\
                                        <h4 class="pull-left modal-title">'+'{$ADDONLANG->T('generateCsrModalTitle')}'+'</h4>\n\
                                    </div>\n\
                                    <form>\n\
                                    <div class="modal-body panel-body" id="modalgenerateCsrBody">\n\
                                        <div class="alert alert-danger hidden" id="modalgenerateCsrDanger">\n\
                                            <strong>Error!</strong> <span></span>\n\
                                        </div>\n\
                                        <form class="form-horizontal"  role="form" id="modalgenerateCsrForm">\n\
                                            <div class="col-md-1"></div>\n\
                                            <div class="col-md-10" style="width:80%;">\n\
                                                  <div class="form-group">\n\
                                                    <label class="control-label" for="C">'+'{$ADDONLANG->T('countryLabel')}'+'</label>\n\
                                                    <select class="form-control  generateCsrInput" id="countryName" name="C" required="">\n\
\n\                                                 ' + countryOptions + '\n\
\n\                                                 </select>\n\
                                                  </div>\n\
                                                  <div class="form-group">\n\
                                                    <label class="control-label" for="ST">'+'{$ADDONLANG->T('stateLabel')}'+'</label>\n\
                                                    <input class="form-control generateCsrInput"  id="stateOrProvinceName" placeholder="'+'{$ADDONLANG->T('statePlaceholder')}'+'" value="{$csrData['state']}" name="ST" required="" type="text">\n\
                                                  </div>\n\
                                                  <div class="form-group">\n\
                                                    <label class="control-label" for="L">'+'{$ADDONLANG->T('localityLabel')}'+'</label>\n\
                                                    <input class="form-control generateCsrInput" id="localityName" placeholder="'+'{$ADDONLANG->T('localityPlaceholder')}'+'" name="L" value="{$csrData['locality']}" required="" type="text">\n\
                                                  </div>\n\
                                                  <div class="form-group">\n\
                                                    <label class="control-label" for="O">'+'{$ADDONLANG->T('organizationLabel')}'+'</label>\n\
                                                    <input class="form-control generateCsrInput" id="organizationName" placeholder="'+'{$ADDONLANG->T('organizationPlaceholder')}'+'" name="O" required="" value="{$csrData['organization']}" type="text">\n\
                                                  </div>\n\
                                                  <div class="form-group">\n\
                                                    <label class="control-label" for="OU">'+'{$ADDONLANG->T('organizationanUnitLabel')}'+'</label>\n\
                                                    <input class="form-control generateCsrInput" id="organizationalUnitName" placeholder="'+'{$ADDONLANG->T('organizationanUnitPlaceholder')}'+'" name="OU" value="{$csrData['org_unit']}" type="text">\n\
                                                  </div>\n\
                                                  <div class="form-group">\n\
                                                    <label class="control-label" for="CN">'+'{$ADDONLANG->T('commonNameLabel')}'+'</label>\n\
                                                    <input class="form-control generateCsrInput" autocomplete="off" id="commonName" placeholder="'+'{if $vars.wildcard}{$ADDONLANG->T('commonNamePlaceholderWildCard')}{else}{$ADDONLANG->T('commonNamePlaceholder')}{/if}'+'" name="CN" value="{$csrData['common_name']}" required="" type="text">\n\
                                                  </div>\n\
                                                  <div class="form-group">\n\
                                                    <label class="control-label" for="EA">'+'{$ADDONLANG->T('emailAddressLabel')}'+'</label>\n\
                                                    <input class="form-control generateCsrInput" id="emailAddress" placeholder="'+'{$ADDONLANG->T('emailAddressPlaceholder')}'+'" name="EA" value="{$csrData['email']}" required="" type="text">\n\
                                                  </div>\n\
                                              </div>\n\
                                            <div class="col-md-1"></div>\n\
                                    </div>\n\
                                    <div class="modal-footer panel-footer">\n\
                                        <button type="button" id="modalgenerateCsrSubmit" class="btn btn-primary">\n\
                                            '+'{$ADDONLANG->T('Submit')}'+'\n\
                                        </button>\n\
                                        <button type="button" class="btn btn-default" data-dismiss="modal">\n\
                                            '+'{$ADDONLANG->T('Close')}'+'\n\
                                        </button>\n\
                                    </div>\n\
                                    </form>\n\
                                </div>\n\
                            </div\n\
                       </div>');


        $("#countryName option[value=\"{$csrData['country']}\"]").attr('selected','');

        $.urlParam = function(name){
            var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
            if (results==null){
               return null;
            }
            else{
               return decodeURI(results[1]) || 0;
            }
        }
        var cert = $.urlParam('cert');
        $('textarea[name="csr"]').after('<div align="middle"><button type="button" id="generateCsrBtn" class="btn btn-default" style="margin:5px">{$ADDONLANG->T('Generate CSR')}</button></div>');
        var token = $('input[name="token"]').val();
        var serviceUrl = 'configuressl.php?cert=' + cert + '&action=generateCsr&json=1&token=' + token,
        generateCsrBtn = $('#generateCsrBtn'),
        generateCsrForm,
        generateCsrModal,
        generateCsrBody,
        generateCsrInput,
        generateCsrSuccessAlert,
        generateCsrDangerAlert,
        generateCsrSubmitBtn,
        body = $('body');
        function assignModalElements() {

            generateCsrModal = $('#modalGenerateCsr');
            generateCsrBody = $('#modalgenerateCsrBody');
            generateCsrBody.contents()
                .   filter(function(){
                return this.nodeType === 8;
            })
            .replaceWith(function(){
                return this.data;
            });

            generateCsrForm = $('#modalgenerateCsrForm');
            generateCsrSubmitBtn = $('#modalgenerateCsrSubmit');
            generateCsrCountryName = $('#countryName');
            generateCsrInput = $('#modalgenerateCsrInput');
            generateCsrDangerAlert = $('#modalgenerateCsrDanger');
            generateCsrStateOrProvinceName = $('#stateOrProvinceName');
            generateCsrLocalityName = $('#localityName');
            generateCsrOrganizationName = $('#organizationName');
            generateCsrOrganizationalUnitName = $('#organizationalUnitName');
            generateCsrCommonName = $('#commonName');
            generateCsrEmailAddress = $('#emailAddress');
        }

        function unbindOnClickForgenerateCsrBtn() {
            generateCsrBtn.attr('onclick', '');
        }

        function bindModalFrogenerateCsrBtn() {
            generateCsrBtn.off().on('click', function () {
                 generateCsrModal.modal('show');
                show(generateCsrSubmitBtn);
                show(generateCsrForm);
                hideAll();
            });
        }

        function bindSubmitBtn() {
            generateCsrSubmitBtn.off().on('click', function () {
                submitgenerateCsrModal();
            });
        }

        function showSuccessAlert(msg) {
            element.before('<div class="alert alert-success" id="generateCsrSuccess">\n\
                                            <strong>Success!</strong> <span>'+ msg +'</span>\n\
                                        </div>');
        }

        function showDangerAlert(msg) {
            show(generateCsrDangerAlert);
            generateCsrDangerAlert.children('span').html(msg);
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
        function closeModal(element) {
            element.modal('toggle');
        }
        function hideAll() {
            hide(generateCsrDangerAlert);
        }

        function anErrorOccurred() {
            showDangerAlert('An error occurred');
        }

        function isJsonString(str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
                return true;
            }
        function validateForm () {
             var fields = [
                    generateCsrCountryName,
                    generateCsrStateOrProvinceName,
                    generateCsrLocalityName,
                    generateCsrOrganizationName,
                    generateCsrOrganizationalUnitName,
                    generateCsrCommonName,
                    generateCsrEmailAddress
                ]
                fields.forEach(function(value, index){
                    value.bind("keyup change input",function() {
                        var empty = false;
                        fields.forEach(function(value2) {
                            if (value2.attr("required") && value2.val() === '') {
                                empty = true;
                            }
                        });
                        if (empty) {
                            generateCsrSubmitBtn.attr('disabled', 'disabled');
                        } else {
                            generateCsrSubmitBtn.removeAttr('disabled');
                        }
                    });
                });
            }
        function submitgenerateCsrModal() {
            $('#generateCsrSuccess').remove();

            addSpiner(generateCsrSubmitBtn);
            disable(generateCsrSubmitBtn);
            var data = {
                generateCsrModal: 'yes',
                countryName: generateCsrCountryName.val(),
                stateOrProvinceName: generateCsrStateOrProvinceName.val(),
                localityName: generateCsrLocalityName.val(),
                organizationName: generateCsrOrganizationName.val(),
                organizationalUnitName: generateCsrOrganizationalUnitName.val(),
                commonName: generateCsrCommonName.val(),
                emailAddress: generateCsrEmailAddress.val()
            };


            //if is reissue add additional serviceid field

            if($('input[name="reissueServiceID"]').length > 0)
            {
                var serviceID = $('input[name="reissueServiceID"]').val();
                data['doNotSaveToDatabase'] = true;
                data['serviceID'] = serviceID;
            }

            $.ajax({
                url: serviceUrl,
                type: "POST",
                data: data,
                json: 1,
                success: function (ret) {
                    var data;
                    if (!isJsonString(ret)) {
                        anErrorOccurred();
                        return;
                    }
                    data = JSON.parse(ret);
                    if (data.success === 1) {
                        showSuccessAlert(data.msg);
                        var csrTextarea = $('textarea[name="csr"]');
                        var generateCsrBtn = $('#generateCsrBtn');

                        csrTextarea.empty();
                        csrTextarea.remove();

                        var tempkey = data.public_key;
                        var newkey = tempkey.substring(0, tempkey.length - 1);

                        generateCsrBtn.before('<textarea name="csr" id="inputCsr" rows="7" readonly class="form-control">'+newkey+'</textarea>');
                        $('input[name="privateKey"]').remove();
                        $('textarea[name="csr"]').closest('.form-group').after('<input class="form-control" type="hidden" name="privateKey" value="'+data.private_key+'" />');
                        closeModal(generateCsrModal);

                    } else {
                        showDangerAlert(data.msg);
                    }
                },
                error: function (jqXHR, errorText, errorThrown) {
                    anErrorOccurred();
                },
                complete: function () {
                    removeSpiner(generateCsrSubmitBtn);
                    enable(generateCsrSubmitBtn);
                }
            });
        }
        assignModalElements();
        unbindOnClickForgenerateCsrBtn();
        bindModalFrogenerateCsrBtn();
        validateForm();
        bindSubmitBtn();
    });
    var fillVars = JSON.parse('{$fillVars}');
    for (var i = 0; i < fillVars.length; i++) {
        if(fillVars[i].name === 'privateKey') {
            $('input[name="privateKey"]').remove();
            $('textarea[name="csr"]').closest('.form-group').after('<input class="form-control" type="hidden" name="privateKey" value="'+fillVars[i].value+'" />');
        }
    }

</script>
