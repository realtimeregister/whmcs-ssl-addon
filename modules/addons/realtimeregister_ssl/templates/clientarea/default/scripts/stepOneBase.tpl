<div id="divhideme" style="display: none"></div>
<script type="text/javascript">
    $(document).ready(function () {

        {if $domains && $auto_install_panel}

            const alertInfo = $('.alert-info').first();
            const csrInput = $('#inputCsr');
            csrInput.attr('readonly', '');

            $('<div><input style="width: 15px;height: 15px;float: left;" type="checkbox" id="csrReadOnly" />' +
                '<label for="csrReadOnly" style="font-size: 13px;float: left;margin-top: ' +
                '-20px;margin-left: 20px;">{$ADDONLANG->T('csrReadOnly')}</label></div>').insertBefore(csrInput);

            $('#csrReadOnly').on('change', e => {
                if (e.target.checked) {
                    csrInput.removeAttr('readonly');
                } else {
                    csrInput.attr('readonly', '');
                }
            });

            alertInfo.after('<div class="card-body select-cpanel-server">' +
                '<h2>{$ADDONLANG->T('Choose a domain')}</h2>' +
                '<select id="step-type-data">' +
                '<option value="custom">{$ADDONLANG->T('Custom domain')}</option>' +
                '</select>' +
                '<div style="margin-top: 20px;" class="form-group">' +
                '</div>');
            alertInfo.hide();

            {foreach $domains as $domain}
                $('#step-type-data').append('<option value="{$domain}">{$domain}</option>');
            {/foreach}

            $('#step-type-data').on('change', (e) => {
                $('input[name="CN"]').val(e.target.value);
            })

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

        $('input, textarea, select').addClass('form-control');

        //remove (Required if Organization Name is set) comment
        const jobTitleInput = $('input[name="jobtitle"]');
        const jobTitleLabel = jobTitleInput.parent().find('label');//for simplicity template

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
