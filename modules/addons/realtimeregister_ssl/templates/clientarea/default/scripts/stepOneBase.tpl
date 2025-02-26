<div id="divhideme" style="display: none"></div>
<script type="text/javascript">
    $(document).ready(function () {

        {if $domains}
            const alertInfo = $('.alert-info').first();

            const csrInput = $('#inputCsr');

            {if $auto_install_panel}
                csrInput.attr('readonly', '');

                $('<div><input style="width: 15px;height: 15px;float: left;" type="checkbox" id="csrReadOnly">' +
                    '<label for="csrReadOnly" style="font-size: 13px;float: left;margin-top: ' +
                    '-20px;margin-left: 20px;">{$ADDONLANG->T('csrReadOnly')}</label></div>').insertBefore(csrInput);

                $('#csrReadOnly').change(e => {
                    csrInput.attr('readonly', !e.target.checked);
                });
            {/if}

            alertInfo.after('<div class="card-body">' +
                '<h2>{$ADDONLANG->T('Choose a domain')}</h2>' +
                '<select id="step-type-data">' +
                '<option value="">{$ADDONLANG->T('Custom domain')}</option>' +
                '</select>' +
                '<div style="margin-top: 20px;" class="form-group">' +
                '<input id="custom-hostname" placeholder="Type your domainname here">' +
                '<div class="form-horizontal" style="margin-top:20px;">' +
                '   <div class="row">' +
                '       <div class="col-6">' +
                '           <label class="radio-inline">' +
                '               <input type="radio" name="csr-type" value="1" class=""> {$ADDONLANG->T('createCsr')}' +
                '           </label>' +
                '       </div>' +
                '       <div class="col-6">' +
                '           <label class="radio-inline">' +
                '               <input type="radio" name="csr-type" value="2" class=""> {$ADDONLANG->T('haveCsr')}'+
                '           </label>' +
                '       </div>' +
                '   </div>' +
                '</div>');
            alertInfo.hide();

            const customHostname = $('#custom-hostname');
            customHostname.on('change', e => {
                $('input[name="CN"]').val(e.target.value);
            });

            {foreach $domains as $domain}
                $('#step-type-data').append('<option value="{$domain}">{$domain}</option>');
            {/foreach}

            $('#step-type-data').on('change', (e) => {
                if (e.target.value !== '') {
                    $('input[name="CN"]').val(e.target.value);
                    customHostname.hide();
                } else {
                    customHostname.show();
                    customHostname.trigger('change');
                }
            })

            $('input[name="csr-type"]').on('change', e => {
                let inputCsr = $('#inputCsr');
                if (e.target.value === '1') {
                    // Help the user creating a CSR
                    $('#generateCsrBtn').show();

                    // We do want to show the csr, if one has been created, so we check if the csr field is empty
                    if (inputCsr.val().length < 100) {
                        $("label[for=inputCsr]").hide();
                        inputCsr.hide();
                    }
                    inputCsr.attr('readonly', true);
                } else if(e.target.value === '2') {
                    // Provide your own CSR
                    inputCsr.show();
                    $("label[for=inputCsr]").show();
                    inputCsr.attr('readonly', false);
                    $('#generateCsrBtn').hide();
                }
            });

            if ($('input[name=csr-type]:checked').val() === undefined) {
                $('input[name="csr-type"][value="1"]').attr('checked', 'checked')
            }
            $('input[name="csr-type"]:checked').trigger('change');
        {/if}

        const brand = JSON.parse('{$brand}');
        $('textarea[name="csr"]').closest('.form-group')
            .after('<input class="form-control" type="hidden" name="sslbrand" value="' + brand + '" />');

        const element = $('#divhideme').closest('.form-group');
        //for control template
        if (element.parent()[0].className === 'panel-body') {
            element.parent().closest('.panel').remove();
        }
        //for six template
        else if (element.parent()[0].className === 'form-horizontal') {
            element.parent().remove();
        }

        $('input:not([type="radio"]), textarea, select').addClass('form-control');

        //remove (Required if Organization Name is set) comment
        const jobTitleInput = $('input[name="jobtitle"]');
        jobTitleInput.val("  ")
        const jobTitleLabel = jobTitleInput.parent().find('label');

        jobTitleInput.parent().html(jobTitleInput);
        //for simplicity template
        if (jobTitleInput.parent().find('label').length === 0) {
            jobTitleInput.before(jobTitleLabel);
        }

        if ($('textarea[name="fields[sans_domains]"]').length > 1) {
            $('label[for="inputAdditionalField"]')[1].remove();
            $('textarea[name="fields[sans_domains]"]')[1].remove();
        }
        if ($('input[name="fields[org_region]"]').length > 1) {
            $('input[name="fields[org_region]"]')[1].remove();
        }

        $('label[for="inputAdditionalField"]').each(function (index) {
            if ($(this).text() === '') {
                $(this).parent('.row').parent('fieldset').remove();
            }
        });
    });
</script>
