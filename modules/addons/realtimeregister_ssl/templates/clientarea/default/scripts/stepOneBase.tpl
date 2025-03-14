<div id="divhideme" style="display: none">

</div>
<script type="text/javascript">
    $(document).ready(function () {
{*        {if $domains}*}
            const alertInfo = $('.alert-info').first();

            $('<p class="auto_install_warning hidden"><small>{$ADDONLANG->T('csrReadOnly')}</small></p>')
                .insertBefore($('#inputCsr'));

            alertInfo.after('<div class="card-body">' +
                '<h2>{$ADDONLANG->T('Choose a domain')}</h2>' +
                '<select id="step-type-data" name="step-type-data">' +
                '<option value="">{$ADDONLANG->T('Custom domain')}</option>' +
                '</select>' +
                '<div style="margin-top: 20px;" class="form-group">' +
                '<input id="custom-hostname" name="custom-hostname" placeholder="Type your domainname here">' +
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

            for (const [key,value] of Object.entries(JSON.parse('{$fillVars}'))) {
                $('input[name="' + key + '"]').val(value);
                $('textarea[name="' + key + '"]').val(value);
                $('select[name="' + key + '"]').val(value);
            }

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
                if (e.target.value === '1') {
                    // Help the user creating a CSR
                    $('.auto_install_warning').hide();
                    $('#generateCsrBtn').show();

                    // We do want to show the csr, if one has been created, so we check if the csr field is empty
                    if ($('#inputCsr').val().length < 100) {
                        $("label[for=inputCsr]").hide();
                        $('#inputCsr').hide();
                    } else {
                        $('#inputCsr').show();
                    }
                    $('#inputCsr').attr('readonly', true);
                } else if(e.target.value === '2') {
                    {if $auto_install_panel}
                        $('.auto_install_warning').show();
                    {/if}
                    // Provide your own CSR
                    $('#inputCsr').show();
                    $("label[for=inputCsr]").show();
                    $('#inputCsr').attr('readonly', false);
                    $('#generateCsrBtn').hide();
                }
            });

            if ($('input[name=csr-type]:checked').val() === undefined) {
                $('input[name="csr-type"][value="1"]').attr('checked', 'checked')
            }
            $('input[name="csr-type"]:checked').trigger('change');
{*        {/if}*}

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

