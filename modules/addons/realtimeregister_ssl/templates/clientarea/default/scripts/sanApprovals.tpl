<script type="text/javascript">
    $(document).ready(function () {
        var fillVars = JSON.parse('{$fillVars}');
        var brand = JSON.parse('{$brand}');
        var disabledValidationMethods = JSON.parse('{$disabledValidationMethods}');

        var mainDomainDcvMethod = '';
        for (var i = 0; i < fillVars.length; i++) {
             if(fillVars[i].name === "fields[dcv_method]") {
                mainDomainDcvMethod = fillVars[i].value;
             }
        }
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

        var checkwildcard = false;
        
        function replaceRadioInputs(sanEmails) {
            var template = $('input[value="Array"]').closest('.row'),
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
                template = $('input[value="Array"]').closest('.panel-body').find('div');
            }
            
           
            template.hide();
            $('input[value="Array"]').remove();

            $.each(sanEmails, function (domain, emails) {

                if(domain.includes('*.'))
                {
                    checkwildcard = true;
                }

                selectDcvMethod = '<div class="form-group"><select style="width:65%;" type="text" name="selectName" class="form-control">';
                selectDcvMethod +='<option value="EMAIL">'+'{$MGLANG->T('dropdownDcvMethodEmail')}'+'</option>';
                selectDcvMethod += '<option value="HTTP">'+'{$MGLANG->T('dropdownDcvMethodHttp')}'+'</option>';
                selectDcvMethod += '<option value="DNS">'+'{$MGLANG->T('dropdownDcvMethodDns')}'+'</option>';

                selectDcvMethod += '</select>';


                partHtml = partHtml + selectDcvMethod.replace('name="selectName"', getNameForSelectMethod(x, domain));
                selectEmailHtml = selectBegin.replace('name="selectName"', getNameForSelectEmail(x, domain));
                
                if(jQuery.inArray('email', disabledValidationMethods) >= 0)
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
        
        replaceRadioInputs(JSON.parse('{$sanEmails}'));

        $('#containerApprovalMethodEmail').parent('div').prev('label').prev('h2').hide();
        $('#containerApprovalMethodEmail').parent('div').prev('label').hide();
        $('#containerApprovalMethodEmail').parent('div').prev('label').prev('label').prev('h2').hide();
        $('#containerApprovalMethodEmail').parent('div').prev('label').prev('label').hide();

        $('#containerApprovalMethodEmail p').next('div').removeClass('col-sm-offset-1');
        $('#containerApprovalMethodEmail p').next('div').removeClass('col-sm-10');
        $('#containerApprovalMethodEmail p').prev('div.alert').hide();
        $('#containerApprovalMethodEmail p').hide();

        if(checkwildcard)
        {
            $('select[name="dcvmethodMainDomain"] option[value="HTTP"]').remove();
        }
        
        $('body').on('change','select[name^="dcvmethod"]',function(){
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
                domain = domain.replace("*", "___"); 
                if(method !== 'EMAIL') {
                    $('select[name="approveremails'+domain+'"]').addClass('hidden');
                } else {
                    $('select[name="approveremails'+domain+'"]').removeClass('hidden'); 
                }
            }
            
            var domainname = $(this).parent('div.form-group').parent('td').prev().text();
            if(domainname.indexOf('*.') >= 0){
                $(this).find('option[value="HTTP"]').remove();
            }
            
        });

        if(jQuery.inArray('email', disabledValidationMethods) >= 0)
        {
            $('#selectDcvMethodsTable').find('th:eq(2)').text('');
            //replace page langs if email method disabled
            $('#selectDcvMethodsTable').closest('form').find('h2:first').text('{$MGLANG->T('sslcertSelectVerificationMethodTitle')}');  
            $('#selectDcvMethodsTable').closest('form').find('p:first').text('{$MGLANG->T('sslcertSelectVerificationMethodDescription')}'); 
        }  
            
        if (!$('select[name="approveremail"] option').length)
        {
            $('select[name="dcvmethodMainDomain"] option[value="EMAIL"]').remove();
            $('select[name="approveremail"]').hide();
        } 
        
        $('select[name^="dcvmethod"]').change();
        
        $.each(JSON.parse('{$sanEmails}'), function (domain, emails) {

            if (!$('select[name="approveremails['+domain+']"] option').length)
            {
                $('select[name="dcvmethod['+domain+']"] option[value="EMAIL"]').remove();
                $('select[name="approveremails['+domain+']"]').hide();
            } 

        });
        
            
        {literal}//var sanEmails = JSON.parse('{\"friz.pl\":[\"admin@friz.pl\",\"administrator@friz.pl\"],\"kot.pl\":[\"admin@kot.pl\",\"administrator@kot.pl\"]}');{/literal} 
    });
</script>
