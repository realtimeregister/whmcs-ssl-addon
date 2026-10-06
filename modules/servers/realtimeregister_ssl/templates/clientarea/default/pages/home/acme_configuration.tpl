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
                        <td style="width: 25%;"><label for="organization">{$ADDONLANG->absoluteT('clientareacompanyname')}</label></td>
                        <td><input type="text" id="organization" class="form-control" value="{$prefillOrganization}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="address">{$ADDONLANG->T('addressLabel')}</label></td>
                        <td><input type="text" id="address" class="form-control" value="{$prefillAddress}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="city">{$ADDONLANG->absoluteT('clientareacity')}</label></td>
                        <td><input type="text" id="city" class="form-control" value="{$prefillCity}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="state">{$ADDONLANG->absoluteT('clientareastate')}</label></td>
                        <td><input type="text" id="state" class="form-control" value="{$prefillState}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="postalCode">{$ADDONLANG->absoluteT('clientareapostcode')}</label></td>
                        <td><input type="text" id="postalCode" class="form-control" value="{$prefillPostalCode}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="country">{$ADDONLANG->T('countryLabel')}</label></td>
                        <td>
                            <select id="country" class="form-control">
                                {foreach $countries as $code => $name}
                                    <option value="{$code}" {if $code == $prefillCountry}selected{/if}>{$name}</option>
                                {/foreach}
                            </select>
                        </td>
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
                        <td style="width: 25%;"><label for="approverFirstName">{$ADDONLANG->absoluteT('clientareafirstname')}</label></td>
                        <td><input type="text" id="approverFirstName" class="form-control" value="{$prefillFirstName}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="approverLastName">{$ADDONLANG->absoluteT('clientarealastname')}</label></td>
                        <td><input type="text" id="approverLastName" class="form-control" value="{$prefillLastName}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="approverJobTitle">{$ADDONLANG->T('approverJobTitleLabel')}</label></td>
                        <td><input type="text" id="approverJobTitle" class="form-control" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="approverEmail">{$ADDONLANG->absoluteT('clientareaemail')}</label></td>
                        <td><input type="email" id="approverEmail" class="form-control" value="{$prefillEmail}" /></td>
                    </tr>
                    <tr>
                        <td style="width: 25%;"><label for="approverVoice">{$ADDONLANG->absoluteT('clientareaphonenumber')}</label></td>
                        <td><input type="tel" id="approverVoice" class="form-control" value="{$prefillVoice}" /></td>
                    </tr>
                </tbody>
            </table>
                {/if}

            <button type="submit" class="btn btn-primary" id="acmeSubmitBtn">
                <i id="addDomainsSpinner" class="fa fa-spinner fa-spin" style="display:none; margin-right:5px;"></i>
                {$ADDONLANG->T('acmeSubmitConfiguration')}
            </button>
        </form>
        </div>
    </div>

    <script type="text/javascript">
        $(document).ready(function () {
            $('#Primary_Sidebar-Service_Details_Actions-Custom_Module_Button_Reissue_Certificate').hide();
            const serviceUrl = 'clientarea.php?action=productdetails&id={$serviceid}&json=1';
            const $approverVoice = $('#approverVoice');
            const hasPhonePicker = $approverVoice.length && typeof $.fn.intlTelInput === 'function';
            const $country = $('#country');

            if (hasPhonePicker) {
                const initialCountry = ($country.val() || 'us').toLowerCase();
                $approverVoice.intlTelInput({
                    initialCountry: initialCountry,
                    preferredCountries: [initialCountry, 'us', 'gb'].filter((v, i, a) => a.indexOf(v) === i),
                    autoPlaceholder: 'polite',
                    separateDialCode: true
                });

                $country.on('change', function () {
                    if ($approverVoice.val() === '') {
                        $approverVoice.intlTelInput('setCountry', $(this).val().toLowerCase());
                    }
                });
            }

            // Convert the approver phone number to E.164a (+CC.NNNNNNNN), as expected by the API
            function getApproverVoice() {
                const raw = $.trim($approverVoice.val());
                if (!raw || !hasPhonePicker) {
                    return raw;
                }
                const dialCode = $approverVoice.intlTelInput('getSelectedCountryData').dialCode;
                if (!dialCode) {
                    return raw;
                }
                // getNumber() defaults to E.164, but returns an empty string when the utils script isn't loaded
                let digits = $approverVoice.intlTelInput('getNumber').replace(/\D/g, '');
                if (digits) {
                    digits = digits.indexOf(dialCode) === 0 ? digits.substring(dialCode.length) : digits;
                } else {
                    digits = raw.replace(/\D/g, '').replace(/^0+/, '');
                }
                return digits ? '+' + dialCode + '.' + digits : '';
            }

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
                postData.approverVoice     = getApproverVoice();
                {/if}

                const $btn = $('#acmeSubmitBtn');
                $btn.prop('disabled', true);
                $('#acmeSubmitSpinner').show();

                $.post(serviceUrl, postData, function (result) {
                    const payload = JSON.parse(result.replace("<JSONRESPONSE#", "").replace("#ENDJSONRESPONSE>", ""));
                    if (payload.result === 'error') {
                        $btn.prop('disabled', false);
                        $('#acmeSubmitSpinner').hide();
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
