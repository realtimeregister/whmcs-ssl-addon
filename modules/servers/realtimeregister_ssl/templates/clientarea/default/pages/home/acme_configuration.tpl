<script type="text/javascript" src="{$assetsURL}/js/addonLibs.js"></script>

    <div class="panel panel-default" style="text-align: left">
        <div class="panel-heading">
            <h2>{$ADDONLANG->T('acmeSubscriptionConfigurationTitle')}</h2>
        </div>
        <div class="panel-body">
            <form id="acmeConfigurationForm">
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
                        <td style="width: 25%; vertical-align: top;">
                            <label for="acmeDomainsInput">{$ADDONLANG->T('acmeDomainsLabel')}</label></td>
                        <td>
                            <textarea id="acmeDomainsInput" rows="5" class="form-control"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>

            {if $isOrganizationRequired}
            <div style="margin-bottom: 10px;">
                <strong>{$ADDONLANG->T('acmeOrganizationFieldsTitle')}</strong>
                <p style="margin: 5px 0 0 0;">
                    {$ADDONLANG->T('acmeOrganizationFieldsDescription')}
                </p>
            </div>
            <table class="table table-bordered" style="margin-bottom: 10px;">
                <tbody>
                    <tr>
                        <td style="width: 25%;"><label for="organization">{$ADDONLANG->T('organizationLabel')}</label></td>
                        <td><input type="text" id="organization" class="form-control" value="{$prefillOrganization}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="address">{$ADDONLANG->T('addressLabel')}</label></td>
                        <td><input type="text" id="address" class="form-control" value="{$prefillAddress}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="city">{$ADDONLANG->T('cityLabel')}</label></td>
                        <td><input type="text" id="city" class="form-control" value="{$prefillCity}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="state">{$ADDONLANG->T('stateLabel')}</label></td>
                        <td><input type="text" id="state" class="form-control" value="{$prefillState}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="postalCode">{$ADDONLANG->T('postalCodeLabel')}</label></td>
                        <td><input type="text" id="postalCode" class="form-control" value="{$prefillPostalCode}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="country">{$ADDONLANG->T('countryLabel')}</label></td>
                        <td><input type="text" id="country" class="form-control" placeholder="e.g. US" maxlength="2" value="{$prefillCountry}" /></td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-bottom: 10px;">
                <strong>{$ADDONLANG->T('acmeApproverFieldsTitle')}</strong>
                <p style="margin: 5px 0 0 0;">
                    {$ADDONLANG->T('acmeApproverFieldsDescription')}
                </p>
            </div>
            <table class="table table-bordered" style="margin-bottom: 10px;">
                <tbody>
                    <tr>
                        <td style="width: 25%;"><label for="approverFirstName">{$ADDONLANG->T('approverFirstNameLabel')}</label></td>
                        <td><input type="text" id="approverFirstName" class="form-control" value="{$prefillFirstName}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="approverLastName">{$ADDONLANG->T('approverLastNameLabel')}</label></td>
                        <td><input type="text" id="approverLastName" class="form-control" value="{$prefillLastName}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="approverJobTitle">{$ADDONLANG->T('approverJobTitleLabel')}</label></td>
                        <td><input type="text" id="approverJobTitle" class="form-control" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="approverEmail">{$ADDONLANG->T('approverEmailLabel')}</label></td>
                        <td><input type="email" id="approverEmail" class="form-control" value="{$prefillEmail}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="approverVoice">{$ADDONLANG->T('approverVoiceLabel')}</label></td>
                        <td><input type="text" id="approverVoice" class="form-control" /></td>
                    </tr>
                </tbody>
            </table>
                {/if}

            <button type="submit" class="btn btn-primary">{$ADDONLANG->T('acmeSubmitConfiguration')}</button>
        </form>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {
            const serviceUrl = 'clientarea.php?action=productdetails&id={$serviceid}&json=1';

            $('#acmeConfigurationForm').on('submit', function (e) {
                e.preventDefault();

                const domains = $('#acmeDomainsInput').val().split('\n');

                const postData = {
                    'addon-action': 'createSubscription',
                    domains
                };

                {if $isOrganizationRequired}
                postData.organization = $('#organization').val();
                postData.address      = $('#address').val();
                postData.city         = $('#city').val();
                postData.state        = $('#state').val();
                postData.postalCode   = $('#postalCode').val();
                postData.country      = $('#country').val();


                postData.approverFirstName = $('#approverFirstName').val();
                postData.approverLastName  = $('#approverLastName').val();
                postData.approverJobTitle  = $('#approverJobTitle').val();
                postData.approverEmail     = $('#approverEmail').val();
                postData.approverVoice     = $('#approverVoice').val();
                {/if}

                $.post(serviceUrl, postData, function (result) {
                    const payload = JSON.parse(result.replace("<JSONRESPONSE#", "").replace("#ENDJSONRESPONSE>", ""));
                    if (payload.result === 'error') {
                        $('#AddonAlerts').alerts('error', payload.error || '{$ADDONLANG->T('anErrorOccurred')}');
                        return;
                    }
                    const msg = payload.data?.message ?? '{$ADDONLANG->T('acmeConfigurationDone')}';
                    $('#AddonAlerts').alerts('success', msg);
                    setTimeout(() => {
                        location.href = 'clientarea.php?action=productdetails&id={$serviceid}'
                    }, 1200);
                });
            });
        });
    </script>
