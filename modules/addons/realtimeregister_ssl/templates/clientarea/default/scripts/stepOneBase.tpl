<div id="divhideme" style="display: none"></div>
<script type="text/javascript">
    $(document).ready(function () {

        {if $domains && $auto_install_panel}

            $('#inputCsr').attr('readonly','');

            $('<div><input style="width: 15px;height: 15px;float: left;" type="checkbox" id="csrReadOnly" /><label for="csrReadOnly" style="font-size: 13px;float: left;margin-top: -20px;margin-left: 20px;">{$MGLANG->T('csrReadOnly')}</label></div>').insertBefore("#inputCsr");

            $('body').on('change','#csrReadOnly',function (){
                if(this.checked)
                {
                    $('#inputCsr').removeAttr('readonly');
                }
                else
                {
                    $('#inputCsr').attr('readonly','');
                }
            });

            if(!$('.primary-content .card-body .alert-danger').length) {

                let contentPage = $('#inputCsr').parent('div.form-group');

                $(contentPage).hide();
                $(contentPage).parent('div').next("div").hide();
                $(contentPage).parent("div").append('<div class="card-body select-cpanel-server">' +
                    '<h2>{$MGLANG->T('Choose a domain')}</h2>' +
                    '<select id="step-type-data">' +
                    '<option value="custom">{$MGLANG->T('Custom domain')}</option>' +
                    '<optgroup label="Server">' +
                    '</optgroup>' +
                    '</select>' +
                    '<div style="margin-top: 20px;" class="form-group"><button id="goto_next_step" class="btn btn-primary" type="button">{$MGLANG->T('Go to next step')}</button></div>' +
                    '</div>');

                {foreach $domains as $domain}
                    $('#step-type-data optgroup').append('<option value="{$domain}">{$domain}</option>');
                {/foreach}

                $('body').on('click', '#goto_next_step', function () {
                    $('.select-cpanel-server').hide();
                    $('input[name="CN"]').val($('#step-type-data').val());
                    $(contentPage).show();
                });
            } else {
                $('#inputCsr').parent('div').hide();
            }
        {/if}

        var brand = JSON.parse('{$brand}');
        $('textarea[name="csr"]').closest('.form-group').after('<input class="form-control" type="hidden" name="sslbrand" value="' + brand + '" />');

        var element = $('#divhideme').closest('.form-group');
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
        var jobTitleInput = $('input[name="jobtitle"]');         
        var jobTitleLabel = jobTitleInput.parent().find('label');//for simplicity template
        
        jobTitleInput.parent().html(jobTitleInput);        
        //for simplicity template
        if(jobTitleInput.parent().find('label').length === 0) {            
            //$( "p:contains('SANs')").remove();
            jobTitleInput.before(jobTitleLabel);
        }
        if($('textarea[name="fields[sans_domains]"]').length > 1) {
            $('label[for="inputAdditionalField"]')[1].remove();
            $('textarea[name="fields[sans_domains]"]')[1].remove();
        } 
        if($('input[name="fields[org_regions]"]').length > 1) {
            $('input[name="fields[org_regions]"]')[1].remove();
        }

        $('label[for="inputAdditionalField"]').each(function( index ) {
            if($(this).text() == '')
            {
                $(this).parent('.row').parent('fieldset').remove();
            }
        });

    });
</script>
