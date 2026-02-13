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
        <p>Running version: {$version}</p>
        {$form} 
    </div>
</div>

<script>
    $(function () {
        $('button[name="testConnection"]').click(function () {
            const body = {
                api_login: $('#item_default_api_login').val(),
                api_test: $('input[name^="api_test"]').is(":checked")
            }

            JSONParser.request('testConnection', body, function (data) {
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
</script>
