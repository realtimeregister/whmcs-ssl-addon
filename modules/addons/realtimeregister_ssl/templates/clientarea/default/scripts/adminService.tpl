<span id="spanhideme"></span>
<script type="text/javascript">
    $(function () {
        $('#spanhideme').closest('tr').hide();
        const csrfToken = $('#frm1 input[name="token"]').val();

        $('#profileContent').find('#frm1')
            .after('<form id="loginAndRedirectForm" target="_blank" action="index.php?rp=/{$adminpath}/client/{$userid}/login" '
        + 'method="GET"><input type="hidden" name="token" value="' + csrfToken + '" />' +
                + '<input type="hidden" name="goto" value="clientarea.php?action=productdetails&id=3">'
                + '<input type="hidden" name="redirectToProductDetails" value="true"/>'
                +  '<input type="hidden" name="username" value="{$email}"/>'
                + '<input type="hidden" name="serviceID" value="{$serviceid}"/></form>');
        $('#loginAndRedirectForm').attr('method', 'POST');

        $('#btnManage_SSL').removeAttr('onclick');
        $('#btnManage_SSL').on('click', function () {
            $('#loginAndRedirectForm').submit();
        });
    });
</script>

<div class="modal fade" id="importCertificateModal" role="dialog" aria-labelledby="importCertificateModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="importCertificateModalLabel">Import Certificate</h4>
            </div>

            <div class="modal-body" id="importCertificateModalBody">
                <div class="alert alert-danger hidden" id="modalImportDangerAlert">
                    <strong>Error!</strong> <span></span>
                </div>
                <form class="form-horizontal" role="form" id="importCertificateModalForm">
                    <div class="form-group">
                        <label for="importCertIdInput">Certificate ID</label>
                        <input type="text" class="form-control" id="importCertIdInput"
                               placeholder="Enter Certificate ID at Realtime Register"/>

                    </div>
                </form>
            </div>


            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="importCertConfirmBtn">Import</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {

        let serviceUrl = 'clientsservices.php?userid={$userid}&id={$serviceid}',
            importCertificateBtn = $('#btnImport_Certificate'),
            importCertificateModal,
            importCertificateModalBody,
            importCertificateSubmitBtn,
            modalImportSuccessAlert,
            modalImportDangerAlert,
            body = $('body');

        function assignModalElements(init) {
            importCertificateModal = $('#importCertificateModal');
            importCertificateModalBody = $('#importCertificateModalBody');

            if (init) {
                importCertificateModalBody.contents()
                    .filter(function () {
                        return this.nodeType === 8;
                    })
                    .replaceWith(function () {
                        return this.data;
                    });
            }

            if (!init) {
                importCertificateSubmitBtn = $('#importCertConfirmBtn');
                modalImportSuccessAlert = $('#modalImportSuccessAlert');
                modalImportDangerAlert = $('#modalImportDangerAlert');
            }
        }

        function moveModalToBody() {
            body.append(importCertificateModal.clone());
            importCertificateModal.remove();
            assignModalElements(false);
        }

        function unbindOnClick() {
            importCertificateBtn.attr('onclick', '');
        }

        function bindModal() {
            importCertificateBtn.off().on('click', function () {
                importCertificateModal.modal('show');
                hideAlerts();
            });
        }

        function bindSubmitBtn() {
            importCertificateSubmitBtn.off().on('click', function () {
                submitImportModal();
            });
        }

        function showDangerAlert(msg) {
            hide(modalImportSuccessAlert);
            show(modalImportDangerAlert);
            modalImportDangerAlert.children('span').html(msg);
        }

        function hideAlerts() {
            hide(modalImportSuccessAlert);
            hide(modalImportDangerAlert);
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

        function submitImportModal() {

            const data = {
                serviceId: {$serviceid},
                userID: {$userid},
                importModal: true
            };

            data['certificateId'] = $('#importCertIdInput').val();

            hideAlerts();
            addSpinner(importCertificateSubmitBtn);

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
                        importCertificateModal.modal('hide');
                        location.reload();
                    } else {
                        showDangerAlert(data.msg);
                    }

                },
                error: function (jqXHR, errorText, errorThrown) {
                    anErrorOccurred();
                },
                complete: function () {
                    removeSpinner(importCertificateSubmitBtn);
                    enable(importCertificateSubmitBtn);
                }
            });
        }

        assignModalElements(true);
        moveModalToBody();
        unbindOnClick();
        bindModal();
        bindSubmitBtn();
    });
</script>