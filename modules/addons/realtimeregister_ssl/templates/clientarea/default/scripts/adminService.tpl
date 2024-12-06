<span id="spanhideme"></span>
<script type="text/javascript">
    $(document).ready(function () {
        let hideMe = $('#spanhideme');
        hideMe.closest('tr').hide();

        {if $version == '8'}
        let tokenf = $('#frm1 input[name="token"]').val();

        $('#profileContent').find('#frm1').after('<form id="loginAndRedirectForm" target="_blank" action="index.php?rp=/{$adminpath}/client/{$userid}/login" method="GET"><input type="hidden" name="token" value="' + tokenf + '" /><input type="hidden" name="goto" value="clientarea.php?action=productdetails&id=3"><input type="hidden" name="redirectToProductDetails" value="true"/><input type="hidden" name="username" value="{$email}"/><input type="hidden" name="serviceID" value="{$serviceid}"/></form>');
        $('#loginAndRedirectForm').attr('method', 'POST');
        {else}
        $('#profileContent').find('#frm1').after('<form id="loginAndRedirectForm" target="_blank" action="../dologin.php?language=" action="POST"><input type="hidden" name="redirectToProductDetails" value="true"/><input type="hidden" name="username" value="{$email}"/><input type="hidden" name="serviceID" value="{$serviceid}"/></form>');
        {/if}
        $('#btnManage_SSL').removeAttr('onclick');
        $('#btnManage_SSL').on('click', function (e) {
            //$('#modcmdbtns').css('opacity', '0.2');
            //$('#modcmdworking').css('display', 'block').css('text-align', 'left').css('position', 'relative').css('left', '50px').css('bottom', '60px').css('z-index', '1');    
            $('#loginAndRedirectForm').submit();
        });
    });
</script>

<div class="modal fade" id="modalReissue" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content panel panel-primary">
            <div class="modal-header panel-heading">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title">Reissue Certificate</h4>
            </div>
            <div class="modal-body panel-body" id="modalReissueBody">
                <div class="alert alert-success hidden" id="modalReissueSuccessAlert">
                    <strong>Success!</strong> <span></span>
                </div>
                <div class="alert alert-danger hidden" id="modalReissueDangerAlert">
                    <strong>Error!</strong> <span></span>
                </div>

                <form class="form-horizontal" role="form" id="modalReissueForm">
                    <input type="hidden" name="formStep" id="modalReissueFormStepInput">
                    <div class="clearfix addon-js-step-one"></div>
                    <br class="addon-js-step-one">
                    <div class="form-group addon-js-step-one">
                        <label class="col-sm-3 control-label">CSR</label>
                        <div class="col-sm-9">
                            <textarea rows="3" class="form-control" name="csr" id="modalReissueCsrInput">
-----BEGIN CERTIFICATE REQUEST-----

-----END CERTIFICATE REQUEST-----
                            </textarea>
                        </div>
                    </div>

                    <div class="clearfix addon-js-step-one"></div>
                    <br class="addon-js-step-one">

                    {if $sansLimit}
                        <div class="form-group addon-js-step-one">
                            <label class="col-sm-3 control-label">SAN Single Domains ({$sansLimit})</label>
                            <div class="col-sm-9">
                                <textarea rows="3" class="form-control" name="sanDomains"
                                          id="modalReissueSansInput"></textarea>
                            </div>
                        </div>
                    {/if}

                    <div class="clearfix addon-js-step-one"></div>
                    <br class="addon-js-step-one">

                    {if $sansLimitWildcard}
                        <div class="form-group addon-js-step-one">
                            <label class="col-sm-3 control-label">SAN Wildcard Domains ({$sansLimitWildcard})</label>
                            <div class="col-sm-9">
                                <textarea rows="3" class="form-control" name="sanDomainsWildcard"
                                          id="modalReissueSansWildcardInput"></textarea>
                            </div>
                        </div>
                    {/if}

                    <div class="form-group addon-js-step-two">
                            <div id="modalReissueEmailApprovalsArea"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer panel-footer">
                <button type="button" id="modalReissueContinue" class="btn btn-primary addon-js-step-one">
                    Continue
                </button>
                <button type="button" id="modalReissueSubmit" class="btn btn-primary addon-js-step-two">
                    Submit
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {

        let serviceUrl = 'clientsservices.php?userid={$userid}&id={$serviceid}',
            reissueBtn = $('#btnReissue_Certificate'),
            reissueModal,
            reissueBody,
            reissueCsrInput,
            reissueCsrDefault,
            reissueSansInput,
            reissueWebServerInput,
            reissueFormStepInput,
            reissueEmailApprovalsArea,
            reissueSubmitBtn,
            reissueSubmitContinue,
            reissueDangerAlert,
            reissueSuccessAlert,
            body = $('body');

        function assignModalElements(init) {
            reissueModal = $('#modalReissue');
            reissueBody = $('#modalReissueBody');

            if (init) {
                reissueBody.contents()
                    .filter(function () {
                        return this.nodeType === 8;
                    })
                    .replaceWith(function () {
                        return this.data;
                    });
            }

            if (!init) {
                reissueSubmitBtn = $('#modalReissueSubmit');
                reissueSubmitContinue = $('#modalReissueContinue');
                reissueCsrInput = $('#modalReissueCsrInput');
                reissueCsrDefault = reissueCsrInput.val();
                reissueSansInput = $('#modalReissueSansInput');
                reissueWebServerInput = $('#modalReissueWebServerInput');
                reissueFormStepInput = $('#modalReissueFormStepInput');
                reissueDangerAlert = $('#modalReissueDangerAlert');
                reissueSuccessAlert = $('#modalReissueSuccessAlert');
                reissueEmailApprovalsArea = $('#modalReissueEmailApprovalsArea');
            }
        }

        function moveModalToBody() {
            body.append(reissueModal.clone());
            reissueModal.remove();
            assignModalElements(false);
        }

        function unbindOnClickForChangeEmailBtn() {
            reissueBtn.attr('onclick', '');
        }

        function bindModalFroChangeEmailBtn() {
            reissueBtn.off().on('click', function () {
                reissueModal.modal('show');
                switchToStepOne();
                hideAlerts();
            });
        }

        function bindSubmitBtn() {
            reissueSubmitBtn.off().on('click', function () {
                submitReissueModal();
            });
            reissueSubmitContinue.off().on('click', function () {
                submitReissueModal();
            });
        }

        function showSuccessAlert(msg) {
            show(reissueSuccessAlert);
            hide(reissueDangerAlert);
            reissueSuccessAlert.children('span').html(msg);
        }

        function showDangerAlert(msg) {
            hide(reissueSuccessAlert);
            show(reissueDangerAlert);
            reissueDangerAlert.children('span').html(msg);
        }

        function hideAlerts() {
            hide(reissueSuccessAlert);
            hide(reissueDangerAlert);
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

        function addSpinner(element) {
            element.append('<i class="fa fa-spinner fa-spin"></i>');
        }

        function removeSpinner(element) {
            element.find('.fa-spinner').remove();
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

        function submitReissueModal() {

            let data = {
                reissueModal: 'yes',
                serviceId: {$serviceid},
                userID: {$userid},
            }, formData = $('#modalReissueForm').serializeArray();

            if (reissueFormStepInput.val() === 'one') {
                data['action'] = 'getApprovals';
            } else {
                data['action'] = 'reissueCertificate';
            }

            data['csr'] = $('textarea#modalReissueCsrInput').val();
            data['sanDomains'] = $('textarea#modalReissueSansInput').val();
            data['sanDomainsWildcard'] = $('textarea#modalReissueSansWildcardInput').val();

            hideAlerts();
            addSpinner(reissueSubmitContinue);
            addSpinner(reissueSubmitBtn);

            const dcv = []
            {literal}
                $('select[name^="dcvmethod"]').each( function () {
                    const commonName = this.name.match(/\[(.+)]/)[1];
                    const entry = {commonName, type: this.value};
                    if (this.value === 'EMAIL') {
                        entry.email = $('select[name="approveremails[' + commonName + ']"').val();
                    }
                    dcv.push(entry)
                });
            {/literal}
            data.dcv = dcv;

            $.ajax({
                type: "POST",
                url: serviceUrl,
                data: data,
                success: function (ret) {
                    if (!isJsonString(ret)) {
                        anErrorOccurred();
                        return;
                    }
                    const data = JSON.parse(ret);
                    if (data.success === 1) {
                        if (reissueFormStepInput.val() === 'one') {
                            renderDcvForm(data.data);
                            switchToStepTwo();
                        } else {
                            showSuccessAlert(data.msg);
                            switchToStepThree();
                        }
                    } else {
                        showDangerAlert(data.msg);
                    }

                },
                error: function (jqXHR, errorText, errorThrown) {
                    anErrorOccurred();
                },
                complete: function () {
                    removeSpinner(reissueSubmitContinue);
                    enable(reissueSubmitContinue);
                    removeSpinner(reissueSubmitBtn);
                    enable(reissueSubmitBtn);
                }
            });
        }

        assignModalElements(true);
        moveModalToBody();
        unbindOnClickForChangeEmailBtn();
        bindModalFroChangeEmailBtn();
        bindSubmitBtn();

        function getDcvRow(title, methods, emails) {
            return '<tr><td>' + title + '</td><td>' + methods + '</td><td>' + emails + '</td></tr>';
        }

        function renderDcvForm(sanEmails) {
            let dcvForm = '',
                tableBegin = '<div class="col-sm-10 col-sm-offset-1"><table id="selectDcvMethodsTable" class="table"><thead><tr><th>'
                    + '{$ADDONLANG->T('stepTwoTableLabelDomain')}' + '</th><th>' + '{$ADDONLANG->T('stepTwoTableLabelDcvMethod')}'
                    + '</th><th>' + '{$ADDONLANG->T('stepTwoTableLabelEmail')}' + '</th></tr></thead>',
                tableEnd = '</table></div>',
                selectBegin = '<div class="form-group"><select type="text" name="[[placeholder]]" class="form-control">',
                selectEnd = '</select></div>';

            reissueEmailApprovalsArea.parent().find('*').not(reissueEmailApprovalsArea).remove();

            $.each(sanEmails, function (domain, emails) {
                let selectDcvMethod = '<div class="form-group"><select type="text" name="[[placeholder]]" class="form-control">';
                selectDcvMethod += '<option value="EMAIL">' + '{$ADDONLANG->T('dropdownDcvMethodEmail')}' + '</option>';

                if (!domain.includes('*.')) {
                    selectDcvMethod += '<option value="HTTP">' + '{$ADDONLANG->T('dropdownDcvMethodHttp')}' + '</option>';
                }

                selectDcvMethod += '<option value="DNS">' + '{$ADDONLANG->T('dropdownDcvMethodDns')}' + '</option>';
                selectDcvMethod += '</select></div>';

                let dcvPart =  selectDcvMethod.replace('[[placeholder]]', 'dcvmethod[' + domain + ']');
                let selectEmailHtml = selectBegin.replace('[[placeholder]]', 'approveremails[' + domain + ']');

                for (const email of emails) {
                    selectEmailHtml += '<option value=' + email + '>' + email + '</option>'
                }

                selectEmailHtml = selectEmailHtml + selectEnd;
                dcvForm = dcvForm + getDcvRow(domain, dcvPart, selectEmailHtml);
            });

            reissueEmailApprovalsArea.before(tableBegin + dcvForm + tableEnd);
        }

        function switchToStepOne() {
            $('.addon-js-step-one').show();
            $('.addon-js-step-two').hide();
            reissueFormStepInput.val('one');
        }

        function switchToStepTwo() {
            $('.addon-js-step-one').hide();
            $('.addon-js-step-two').show();
            reissueFormStepInput.val('two');
        }

        function switchToStepThree() {
            $('.addon-js-step-one').hide();
            $('.addon-js-step-two').hide();
            reissueCsrInput.val(reissueCsrDefault);
            reissueSansInput.val('');
            reissueFormStepInput.val('three');
            reissueWebServerInput.val('0');
        }
    });
</script>

<div class="modal fade" id="modalView" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content panel panel-primary">
            <div class="modal-header panel-heading">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only">Close</span>
                </button>
                <h4 class="modal-title" id="ModuleSuspendLabel">View Certificate</h4>
            </div>
            <div class="modal-body panel-body" id="modalViewBody">
                <div class="alert alert-success hidden" id="modalViewSuccessAlert">
                    <strong>Success!</strong> <span></span>
                </div>
                <div class="alert alert-danger hidden" id="modalViewDangerAlert">
                    <strong>Error!</strong> <span></span>
                </div>
                <div class="text-center hidden" id="modalViewLoading">
                    Loading...
                </div>
                <form class="form" role="form" id="modalViewForm">
                    <div class="form-group hidden">
                        <label class="col-sm-3 control-label">Certificate (CRT)</label>
                        <textarea class="form-control" onfocus="this.select();" rows="5" id="viewCRTInput"></textarea>
                    </div>
                    <div class="clearfix"></div>

                    <div class="form-group hidden">
                        <label class="col-sm-3 control-label">Intermediate/Chain files</label>
                        <textarea class="form-control" onfocus="this.select();" rows="10"
                                  id="viewCertificateInput"></textarea>
                    </div>
                    <div class="clearfix"></div>

                    <div class="form-group hidden">
                        <label class="col-sm-3 control-label">CSR (Certificate Signing Request)</label>
                        <textarea class="form-control" onfocus="this.select();" rows="5" id="viewCSRInput"></textarea>
                    </div>
                    <div class="clearfix"></div>
                </form>

            </div>
            <div class="modal-footer panel-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {

        let serviceUrl = 'clientsservices.php?userid={$userid}&id={$serviceid}',
            viewBtn = $('#btnView_Certificate'),
            viewModal,
            viewBody,
            viewLoading,
            viewDangerAlert,
            viewSuccessAlert,
            viewCertificateInput,
            viewCRTInput,
            viewCSRInput,
            body = $('body');

        function assignModalElements(init) {
            viewModal = $('#modalView');
            viewBody = $('#modalViewBody');

            if (init) {
                viewBody.contents()
                    .filter(function () {
                        return this.nodeType === 8;
                    })
                    .replaceWith(function () {
                        return this.data;
                    });
            }

            if (!init) {
                viewDangerAlert = $('#modalViewDangerAlert');
                viewSuccessAlert = $('#modalViewSuccessAlert');
                viewLoading = $('#modalViewLoading');
                viewCertificateInput = $('#viewCertificateInput');
                viewCRTInput = $('#viewCRTInput');
                viewCSRInput = $('#viewCSRInput');
            }
        }

        function moveModalToBody() {
            body.append(viewModal.clone());
            viewModal.remove();
            assignModalElements(false);
        }

        function unbindOnClickForViewCertificateBtn() {
            viewBtn.attr('onclick', '');
        }

        function bindModalToViewCertificateBtn() {
            viewBtn.off().on('click', function () {
                viewModal.modal('show');
                fetchCertificate();
            });
        }

        function showSuccessAlert(msg) {
            show(viewSuccessAlert);
            hide(viewDangerAlert);
            viewSuccessAlert.children('span').html(msg);
        }

        function showDangerAlert(msg) {
            hide(viewSuccessAlert);
            show(viewDangerAlert);
            viewDangerAlert.children('span').html(msg);
        }

        function show(element) {
            element.removeClass('hidden');
        }

        function hide(element) {
            element.addClass('hidden');
        }

        function enable(element) {
            element.removeClass('disabled');
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

        function hideAll() {
            hide(viewDangerAlert);
            hide(viewSuccessAlert);
            hide(viewCertificateInput.parent('.form-group'));
            hide(viewCRTInput.parent('.form-group'));
            hide(viewCSRInput.parent('.form-group'));
            show(viewLoading); // xD
        }

        function renderCertificates(data) {
            hide(viewLoading);

            if (typeof data === 'undefined') {
                return;
            }

            if (typeof data.ca !== 'undefined') {
                show(viewCertificateInput.parent('.form-group'));
                viewCertificateInput.val(data.ca);
            }

            if (typeof data.crt !== 'undefined') {
                show(viewCRTInput.parent('.form-group'));
                viewCRTInput.val(data.crt);
            }

            if (typeof data.csr !== 'undefined') {
                show(viewCSRInput.parent('.form-group'));
                viewCSRInput.val(data.csr);
            }
        }

        function fetchCertificate() {

            hideAll();

            let data = {
                viewModal: 'yes',
                serviceId: {$serviceid},
                userID: {$userid},
            };
            $.ajax({
                type: "POST",
                url: serviceUrl,
                data: data,
                success: function (ret) {
                    let data;
                    if (!isJsonString(ret)) {
                        anErrorOccurred();
                        return;
                    }
                    data = JSON.parse(ret);
                    if (data.success === 1) {
                        renderCertificates(data.data);
                    } else {
                        renderCertificates(data.data);
                        showDangerAlert(data.msg);
                    }
                },
                error: function (jqXHR, errorText, errorThrown) {
                    anErrorOccurred();
                }
            });
        }

        assignModalElements(true);
        moveModalToBody();
        unbindOnClickForViewCertificateBtn();
        bindModalToViewCertificateBtn();
    });
</script>

<script type="text/javascript">
    $(document).ready(function () {
        let refreshBtn = $('#btnRefresh');
        refreshBtn.attr('onclick', '');
        refreshBtn.off().on('click', function () {
            location.reload();
        });
    });
</script>