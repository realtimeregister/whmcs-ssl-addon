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
        $(function () {
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
                }
            });
            $('button[name="testConnection"]').click(function () {
                const login = $('#item_default_api_login').val();

                JSONParser.request('testConnection', {api_login: login}, function (data) {
                    if (data.success === true) {
                        $('#AddonAlerts>div[data-prototype="success"]').show();
                        $('#AddonAlerts>div[data-prototype="success"] strong').html(data.message);
                    } else if (data.success === false) {
                        $('#AddonAlerts>div[data-prototype="error"]').show();
                        $('#AddonAlerts>div[data-prototype="error"] strong').html(data.message);
                    }
                }, false);
            });
            $('input[name^="send_expiration_notification_one_time"]')
                .click(e => $('select[name^="renew_invoice_days_one_time"]').prop('disabled', !e.target.checked));
        });
    {/literal}
</script>
