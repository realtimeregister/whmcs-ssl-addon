<script type="text/javascript">
    $(function () {
        let element;
        {if $csrModal}
            //for control template
            if($('#internal-content form').length > 0) {
                element = $('#internal-content form');
            }
            //for six template
            else if($('.main-content form').length > 0) {
                element = $('.main-content form');
            }
            else if($('#main-body .primary-content form').length > 0) {
                element = $('#main-body .primary-content form');
            }
            //for flare template
            else if($('#main-body form').length > 0) {
                element = $('#main-body form');
            }

            element.append(`{$csrModal}`)

            if ($('#generateCsrBtn').length === 0) {
                $('textarea[name="csr"]').after('<div align="middle"><button type="button" id="generateCsrBtn" class="btn btn-default" style="margin:5px">{$ADDONLANG->T('Generate CSR')}</button></div>');
            }
        {else}
            element = $('#order-standard_cart');
        {/if}

        const cert = (new URLSearchParams(window.location.search)).get('cert');
        let token = $('input[name="token"]').val();

        let baseUrl = 'cart.php';
        if (window.location.href.indexOf("configuressl.php")) {
            baseUrl = 'index.php';
        }
        let serviceUrl = baseUrl + '?a=confproduct&cert=' + cert + '&action=generateCsr&json=1&token=' + token,
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
                .filter(function(){
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

            generateCsrDangerAlert.hide();
        }

        function bindModalFrogenerateCsrBtn() {
            generateCsrBtn.off().on('click', function () {
                 generateCsrModal.modal('show');
                generateCsrSubmitBtn.show();
                generateCsrForm.show();
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
            generateCsrDangerAlert.show();
            generateCsrDangerAlert.children('span').html(msg);
        }

        function addSpinner(element) {
            element.append('<i class="fa fa-spinner fa-spin"></i>');
        }

        function removeSpinner(element) {
            element.find('.fa-spinner').remove();
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

        function validateForm() {
            let fields = [
                generateCsrCommonName,
            ]
            fields.forEach(function (value, index) {
                value.bind("keyup change input", function () {
                    let empty = false;
                    fields.forEach(function (value2) {
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

            addSpinner(generateCsrSubmitBtn);
            disable(generateCsrSubmitBtn);
            const data = {
                generateCsrModal: 'yes',
                countryName: generateCsrCountryName.val(),
                stateOrProvinceName: generateCsrStateOrProvinceName.val(),
                localityName: generateCsrLocalityName.val(),
                organizationName: generateCsrOrganizationName.val(),
                organizationalUnitName: generateCsrOrganizationalUnitName.val(),
                commonName: generateCsrCommonName.val(),
                emailAddress: generateCsrEmailAddress.val()
            };

            // if is reissue add additional serviceid field
            if($('input[name="reissueServiceID"]').length > 0) {
                const serviceID = $('input[name="reissueServiceID"]').val();
                data['doNotSaveToDatabase'] = true;
                data['serviceID'] = serviceID;
            }
// console.log('serviceurl:' + serviceUrl);
            $.ajax({
                url: serviceUrl,
                type: "POST",
                data: data,
                json: 1,
                success: function (ret) {
                    if (!isJsonString(ret)) {
                        anErrorOccurred();
                        return;
                    }
                    const data = JSON.parse(ret);
                    if (data.success === 1) {
                        showSuccessAlert(data.msg);
                        const csrTextarea = $('textarea[name="csr"]');

                        csrTextarea.empty();
                        csrTextarea.show();

                        const tempkey = data.public_key;
                        const newkey = tempkey.substring(0, tempkey.length - 1);

                        csrTextarea.val(newkey);
                        $('input[name="privateKey"]').remove();
                        $('textarea[name="csr"]').closest('.form-group').after('<input class="form-control" type="hidden" name="privateKey" value="'+data.private_key+'" />');
                        closeModal(generateCsrModal);
                        $("label[for=inputCsr]").show();

                    } else {
                        showDangerAlert(data.msg);
                    }
                },
                error: function () {
                    anErrorOccurred();
                },
                complete: function () {
                    removeSpinner(generateCsrSubmitBtn);
                    enable(generateCsrSubmitBtn);
                }
            });
        }
        assignModalElements();
        bindModalFrogenerateCsrBtn();
        validateForm();
        bindSubmitBtn();
    });

    Object.entries(JSON.parse('{$fillVars}'))
        .filter(([key, _]) => key === 'privateKey')
        .forEach(([_, value]) => {
                $('input[name="privateKey"]').remove();
                $('textarea[name="csr"]').closest('.form-group')
                    .after('<input class="form-control" type="hidden" name="privateKey" value="' + value + '" />');
            }
        )

</script>
