<script type="text/javascript" src="{$assetsURL}/js/addonLibs.js"></script>

    <div class="panel panel-default" style="text-align: left">
        <div class="panel-heading">
            <h2>{$ADDONLANG->T('acmeSubscriptionConfigurationTitle')}</h2>
        </div>
        <div class="panel-body">
            <div class="alert alert-info" style="margin-bottom: 45px;">
                {$ADDONLANG->T('acmeSubscriptionConfigurationInfo')}
            </div>

            <div style="margin-bottom: 10px;">
                <strong>{$ADDONLANG->T('acmeAddNewDomainsTitle')}</strong>
                <p style="margin: 5px 0 0 0;">
                    {$ADDONLANG->T('acmeAddNewDomainsDescription')}
                </p>
            </div>
            <div>
                <p>{$ADDONLANG->T('availableDomains')}: {$singleDomainsLimit} <br/>
                 {$ADDONLANG->T('availableWildcards')}: {$wildcardDomainsLimit}<p>
            </div>
            <table class="table table-bordered" style="margin-bottom: 10px;">
                <tbody>
                    <tr>
                        <td style="width: 25%; vertical-align: top;">{$ADDONLANG->T('acmeDomainsLabel')}</td>
                        <td>
                            <textarea id="acmeDomainsInput" rows="5" class="form-control"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="button" class="btn btn-primary" id="acmeSubmitConfiguration">{$ADDONLANG->T('acmeSubmitConfiguration')}</button>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {
            const serviceUrl = 'clientarea.php?action=productdetails&id={$serviceid}&json=1';

            $('#acmeSubmitConfiguration').on('click', function () {
                const domains = $('#acmeDomainsInput').val().split('\n');

                $.post(serviceUrl, {
                    'addon-action': 'createSubscription',
                    domains
                }, function (result) {
                    const payload = JSON.parse(result.replace("<JSONRESPONSE#", "").replace("#ENDJSONRESPONSE>", ""));
                    if (payload.result === 'error') {
                        $('#AddonAlerts').alerts('error', payload.error || '{$ADDONLANG->T('anErrorOccurred')}');
                        return;
                    }
                    const msg = payload.data?.message ?? '{$ADDONLANG->T('acmeConfigurationDone')}';
                    $('#AddonAlerts').alerts('success', msg);
                    window.setTimeout(function () { window.location.href = 'clientarea.php?action=productdetails&id={$serviceid}'; }, 1200);
                });
            });
        });
    </script>
