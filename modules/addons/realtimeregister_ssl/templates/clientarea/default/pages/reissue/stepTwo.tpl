<h3>{$ADDONLANG->T('reissueSelectVerificationMethodTitle')}</h3>
<p>{$ADDONLANG->T('reissueSelectVerificationMethodDescription')}</p>

{assign var=val value=0}

<div class="row">
    <div class="col-sm-12">
        <form method="POST" action="{$smarty.server.REQUEST_URI}" class="form-horizontal">
            <input type="hidden" name="stepTwoForm" value="tak">
            <input type="hidden" name="webservertype" value="{$smarty.post.webservertype}">
            <input type="hidden" name="csr" value="{$smarty.post.csr}">
            <input type="hidden" name="sans_domains" value="{$smarty.post.sans_domains}">
            <input type="hidden" name="sans_domains_wildcard" value="{$smarty.post.sans_domains_wildcard}">
            <input type="hidden" name="privateKey" value="{$privateKey}">
            <input type="hidden" name="extraValidation" value="{$extraValidation}">
            <div class="loading">
                Loading...
            </div>
            {if $extraValidation}
            <h3>{$ADDONLANG->T('reissueTwoOrganizationTitle')}</h3>
            <p>{$ADDONLANG->T('reissueTwoOrganizationSubTitle')}</p>
                <div class="form-group">
                    <label class="col-sm-4 col-form-label" for="inputOrgName">{$ADDONLANG->T('organizationLabel')}</label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control" name="orgname" id="inputOrgName" value="{$orgname}" />
                    </div>
                </div>

            <div class="form-group">
                <label class="col-sm-4 col-form-label" for="inputAddress">{$ADDONLANG->T('reissueTwoAdresss')}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="address" id="inputAddress" value="{$address}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 col-form-label" for="inputState">{$ADDONLANG->T('stateLabel')}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="state" id="inputState" value="{$state}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 col-form-label" for="inputCity">{$ADDONLANG->T('reissueTwoCity')}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="city" id="inputCity" value="{$city}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 col-form-label" for="inputPostcode">{$ADDONLANG->T('reissueTwoPostalCode')}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="postcode" id="inputPostcode" value="{$postcode}" />
                </div>
            </div>
            <h3>{$ADDONLANG->T('reissueTwoTitle')}</h3>
            <p>{$ADDONLANG->T('reissueTwoSubTitle')}</p>
            <div class="form-group">
                <label class="col-sm-4 control-label" for="inputFirstName">{$ADDONLANG->T('reissueTwoFirstName')}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="firstname" id="inputFirstName" value="{$firstname}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="inputLastName">{$ADDONLANG->T('reissueTwoLastName')}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="lastname" id="inputLastName" value="{$lastname}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="inputEmail">{$ADDONLANG->T('reissueTwoEmail')}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="email" id="inputEmail" value="{$email}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="inputPhoneNumber">{$ADDONLANG->T('reissueTwoVoice')}</label>
                <div class="col-sm-8">
                    <input type="tel" class="form-control" name="phonenumber" id="inputPhoneNumber" value="{$phonenumber}" />
                </div>
            </div>

            <div class="form-group">
                <label class="col-sm-4 control-label" for="inputJobTitle">{$ADDONLANG->T('reissueTwoJobTitle')}</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" name="jobtitle" id="inputJobTitle" value="{$jobtitle}"/>
                </div>
            </div>
            {/if}
            <div class="col-sm-12 text-center">
                <input id="reissueCertificateButton" type="submit" value="{$ADDONLANG->T('reissueTwoContinue')}" class="btn btn-primary">
            </div>
        </form>
    </div>
</div>
 <script type="text/javascript">
    $(document).ready(function () {
        function getSelectHtml(value, checked) {
            if (checked) {
                var ck = ' selected';
            } else {
                var ck = '';
            }
            return '<option value="' + value + '"' + ck + '>' + value + '</option>'
        }
        function getRowHtml(title, methods, emails) {
            return '<tr><td>' + title + '</td><td>' + methods + '</td><td>' + emails + '</td></tr>';
        }
        function getNameForSelectMethod(x, domain) {
            if (x === 0) {
                return 'name="dcvmethodMainDomain"';
            }
            domain = domain.replace("*", "___");
            return 'name="dcvmethod[' + domain + ']"';
        }
        function getNameForSelectEmail(x, domain) {
            if (x === 0) {
                return 'name="approveremail"';
            }
            domain = domain.replace("*", "___");
            return 'name="approveremails[' + domain + ']"';
        }
        function getTable(tableBegin, tableEnd, body) {
            return tableBegin + body + tableEnd;
        }

        function replaceRadioInputs(sanEmails) {
            var template = $('.loading'),
                    selectEmailHtml = '',
                    fullHtml = '',
                    partHtml = '',
                    tableBegin = '<div class="col-sm-10 col-sm-offset-1"><table id="selectDcvMethodsTable" class="table"><thead><tr><th>'+'{$ADDONLANG->T('stepTwoTableLabelDomain')}'+'</th><th>'+'{$ADDONLANG->T('stepTwoTableLabelDcvMethod')}'+'</th><th>'+'{$ADDONLANG->T('stepTwoTableLabelEmail')}'+'</th></tr></thead>',
                    tableEnd = '</table></div>',
                    selectDcvMethod = '',
                    selectBegin = '<div class="form-group"><select style="width:80%;" type="text" name="selectName" class="form-control">',
                    selectEnd = '</select></div>',
                    x = 0;

            //for template control
            if(template.find('.panel').length > 0) {
                template = $('input[value="loading..."]').closest('.panel-body').find('div');
            }

            template.hide();

            $.each(sanEmails, function (domain, emails) {


                var checkwildcard = false;

                if(domain.includes('*.'))
                {
                    checkwildcard = true;
                }


                selectDcvMethod = '<div class="form-group"><select style="width:65%;" type="text" name="selectName" class="form-control">';
                selectDcvMethod +='<option value="EMAIL">'+'{$ADDONLANG->T('dropdownDcvMethodEmail')}'+'</option>';

                if(!checkwildcard)
                {
                    selectDcvMethod += '<option value="HTTP">'+'{$ADDONLANG->T('dropdownDcvMethodHttp')}'+'</option>';
                }

                selectDcvMethod += '<option value="DNS">'+'{$ADDONLANG->T('dropdownDcvMethodDns')}'+'</option>';
                selectDcvMethod += '</select></div>';

                partHtml = partHtml + selectDcvMethod.replace('name="selectName"', getNameForSelectMethod(x, domain));
                selectEmailHtml = selectBegin.replace('name="selectName"', getNameForSelectEmail(x, domain));

                selectEmailHtml = selectEmailHtml.replace(getNameForSelectEmail(x, domain) + ' class="form-control"', getNameForSelectEmail(x, domain) + ' class="form-control hidden"');


                for (var i = 0; i < emails.length; i++) {
                    selectEmailHtml = selectEmailHtml +  getSelectHtml(emails[i], i === 0);
                }
                selectEmailHtml = selectEmailHtml + selectEnd;
                fullHtml = fullHtml + getRowHtml(domain, partHtml, selectEmailHtml);

                partHtml = '';
                x++;
            });

            template.before(getTable(tableBegin, tableEnd, fullHtml));
            template.remove();
        }

        replaceRadioInputs(JSON.parse('{$approvalEmails}'));

        $('select[name^="dcvmethod"]').change( function (){
            var method = this.value;
            var selectName = this.name;
            var domain = selectName.replace('dcvmethod', '');
            if(domain === 'MainDomain') {
                if(method !== 'EMAIL') {
                    $('select[name="approveremail"]').addClass('hidden');
                } else {
                    $('select[name="approveremail"]').removeClass('hidden');
                }
            } else {
                if(method !== 'EMAIL') {
                    $('select[name="approveremails'+domain+'"]').addClass('hidden');
                } else {
                    $('select[name="approveremails'+domain+'"]').removeClass('hidden');
                }
            }
        });
        $('select[name^="dcvmethod"]').change();
    });
</script>
