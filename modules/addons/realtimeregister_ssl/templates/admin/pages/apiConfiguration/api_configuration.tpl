{if $formError}
    <div class="col-lg-12">
        <div class="note note-danger">
            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
            <p><strong>{$formError}</strong></p>
        </div>
    </div>
{/if}

<div class="panel panel-default">
    <div class="panel-body">
        {$form} 
    </div>
</div>

<!-- Import Data & Configuration Modal  -->
<div class="modal fade bs-example-modal-lg" id="AddonDataMigration" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">{$ADDONLANG->T('modal','migrationData')}</h4>
            </div>
            <div class="modal-body">
                <div class="modal-alerts">
                    <div style="display:none;" data-prototype="error" id="errorModal">
                        <div class="note note-danger">
                            <strong></strong>
                            <a style="display:none;" class="errorID" href=""></a>
                        </div>
                    </div>
                    <div style="display:none;" data-prototype="success" id="successModal">
                        <div class="note note-success">
                            <strong></strong>
                        </div>
                    </div>
                </div> 
                <h4 class="text-center">{$ADDONLANG->T('modal','dataMigrationInfo')} </h4>
                <h4 class="text-center">{$ADDONLANG->T('modal','dataMigrationInfo2')} </h4>
                <div style='padding-left: 15px;'>
                    <ul>
                        <li><h4>{$ADDONLANG->T('modal','dataMigrationInfoAction', '0')}</h4></li>
                        <li><h4>{$ADDONLANG->T('modal','dataMigrationInfoAction', '1')}</h4></li>
                        <li><h4>{$ADDONLANG->T('modal','dataMigrationInfoAction', '2')}</h4></li>
                    </ul> 
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success btn-inverse" id="runMigrationButton" data-dismiss="modal">{$ADDONLANG->T('modal','import')}</button>
                <button type="button" class="btn btn-default btn-inverse" data-dismiss="modal">{$ADDONLANG->T('modal','close')}</button>
            </div>
        </div>
    </div>
</div>
<script>
    {literal}
        function removeErrorStyle(modal)
        {
            $(modal).find('.form-group').removeClass('has-error');
            cleanModalMessage(modal);
        }
        function cleanModalMessage(modal) {
            $(modal).find('#successModal').attr('style', 'display:none;');
            $(modal).find('#errorModal').attr('style', 'display:none;');
        }
        function successModal(info, modal) {
            cleanModalMessage(modal);
            $(modal).find('#successModal').removeAttr('style');
            $(modal).find('#successModal').find('strong').text(info);
        }
        $(document).ready(function () {

            $('.intl-tel-input .country-list .country').click(function () {
                $('input[name="tech_phone_country"]').val($(this).attr('data-country-code'));
            })

            $('input[name="use_admin_contact[]"]').on('click', function () {
                if (!$(this).is(":checked")) {
                    $('input[id^="item_default_tech_"]').prop('readonly', false).prop('required', true);
                    $('#item_default_tech_country').prop('disabled', false).prop('required', true);
                    $('#techCountrHidden').remove();
                } else {
                    var defaultCountry = $('#item_default_tech_country').val();
                    $('#item_default_tech_country').before('<input id="techCountrHidden" name="tech_country" value="' + defaultCountry + '" class="form-control" type="hidden">');
                    $('input[id^="item_default_tech_"]').prop('readonly', true).prop('required', false);
                    $('#item_default_tech_country').prop('disabled', true).prop('required', false);
                    ;

                }
            });
            jQuery('button[name="testConnection"]').click(function () {
                var login = $('#item_default_api_login').val(),
                        password = $('#item_default_api_password').val();

                JSONParser.request('testConnection', {api_login: login, api_password: password}, function (data) {
                    if (data.success == true) {
                        $('#AddonAlerts>div[data-prototype="success"]').show();
                        $('#AddonAlerts>div[data-prototype="success"] strong').html(data.message);
                    } else if (data.success == false) {
                        $('#AddonAlerts>div[data-prototype="error"]').show();
                        $('#AddonAlerts>div[data-prototype="error"] strong').html(data.message);
                    }
                }, false);
            });
            $('input[name^="auto_renew_invoice"], input[name^="display_csr_generator[]"]').on('click', function () {
                //$('input[name="use_admin_contact[]"]').prop('checked', true);                
                if (!$(this).is(":checked")) {
                    $(this).parent().parent().parent().find('select').prop('disabled', true);
                } else {
                    $(this).parent().parent().parent().find('select').prop('disabled', false);
                }
            });
            jQuery('button[name="data_migration"]').click(function () {
                var modal = $("#AddonDataMigration");
                removeErrorStyle($(modal));
                $(modal).find('.fa-spinner').remove();
                $(modal).modal();
            });
            $(document).on('click', '#runMigrationButton', function () {
                var modal = $("#AddonDataMigration");
                $(this).append(' <i class="fa fa-spinner fa-spin" style="font-size:24px"></i>');
                removeErrorStyle($(modal));
                JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&addon-page=apiConfiguration', 'POST');
                JSONParser.request('runMigration', {}, function (data) {

                    if (data.success) {
                        successModal(data.success, $(modal))
                        window.setTimeout(function () {
                            location.reload()
                        }, 5000);
                    }
                }, );
            });
        });
    {/literal}
</script>
