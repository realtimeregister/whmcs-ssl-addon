<h3>{$MGLANG->T('reissueTwoTitle')}</h3>
<p>{$MGLANG->T('reissueTwoSubTitle')}</p>

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
            <div class="loading">
                Loading...
            </div>
            <!--{foreach from=$approvalEmails key=domain item=domainApprovalEmail}
                <h3>{$domain}</h3>
                <div class="form-group">
                    {assign var=firstRadio value=0}
                    {foreach from=$domainApprovalEmail item=approvalEmail}
                        <div class="radio">
                            {if $val === 0}
                                <label><input {if $firstRadio === 0}checked{/if} type="radio" name="approveremail" value="{$approvalEmail}">{$approvalEmail}</label>
                                {else}
                                <label><input {if $firstRadio === 0}checked{/if} type="radio" name="approveremails[{$domain}]" value="{$approvalEmail}">{$approvalEmail}</label>
                                {/if}
                        </div>
                        {assign var=firstRadio value=$firstRadio+1}
                    {/foreach}
                </div>
                {assign var=val value=$val+1}
            {/foreach}-->
            <div class="col-sm-12 text-center">
                <input id="reissueCertificateButton" type="submit" value="{$MGLANG->T('reissueTwoContinue')}" class="btn btn-primary">
            </div>
        </form>
    </div>
</div>
 <script type="text/javascript">
    $(document).ready(function () {
        var disabledValidationMethods = JSON.parse('{$disabledValidationMethods}');

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
        
        function ValidateIPaddress(ipaddress) {  
            if (/^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/.test(ipaddress)) {  
                return true;
            }  
            return false;
        }  
        
        function replaceRadioInputs(sanEmails) {
            var template = $('.loading'),
                    selectEmailHtml = '',
                    fullHtml = '',
                    partHtml = '',
                    tableBegin = '<div class="col-sm-10 col-sm-offset-1"><table id="selectDcvMethodsTable" class="table"><thead><tr><th>'+'{$MGLANG->T('stepTwoTableLabelDomain')}'+'</th><th>'+'{$MGLANG->T('stepTwoTableLabelDcvMethod')}'+'</th><th>'+'{$MGLANG->T('stepTwoTableLabelEmail')}'+'</th></tr></thead>',
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

                if(jQuery.inArray('email', disabledValidationMethods) < 0)  {
                    selectDcvMethod +='<option value="EMAIL">'+'{$MGLANG->T('dropdownDcvMethodEmail')}'+'</option>';
                }
                
                
                if(!checkwildcard)
                {
                    if(jQuery.inArray('http', disabledValidationMethods) < 0)  {
                        selectDcvMethod += '<option value="HTTP">'+'{$MGLANG->T('dropdownDcvMethodHttp')}'+'</option>';
                    }
                }
                if(jQuery.inArray('dns', disabledValidationMethods) < 0)
                {
                    selectDcvMethod += '<option value="DNS">'+'{$MGLANG->T('dropdownDcvMethodDns')}'+'</option>';
                }
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
        
        if(jQuery.inArray('email', disabledValidationMethods) >= 0)
        {
            $('#selectDcvMethodsTable').find('th:eq(2)').text('');
            $('#selectDcvMethodsTable').closest('.row').parent().find('h3:first').text('{$MGLANG->T('reissueSelectVerificationMethodTitle')}');  
            $('#selectDcvMethodsTable').closest('.row').parent().find('p:first').text('{$MGLANG->T('reissueSelectVerificationMethodDescription')}'); 
        }
        
        {literal}//var sanEmails = JSON.parse('{\"friz.pl\":[\"admin@friz.pl\",\"administrator@friz.pl\"],\"kot.pl\":[\"admin@kot.pl\",\"administrator@kot.pl\"]}');{/literal} 
    });
</script>
