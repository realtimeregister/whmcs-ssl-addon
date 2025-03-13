<div class="form-group">
    <label for="csrInput" class="d-block">CSR</label>
    <textarea name="csr" id="csrInput" class="d-block form-control">{$csrData['csr']}</textarea>
    <button type="button" id="generateCsrBtn" class="btn btn-default d-block">Generate CSR</button>
    <label for="dcv">DCV</label>
    <select name="dcv" id="dcv" class="form-control">
        <option value="EMAIL">E-mail</option>
        <option value="HTTP">HTTP</option>
        <option value="DNS">DNS</option>
    </select>
    <div id="approveremails">
        <label for="approveremail">Approver E-mail</label>
        <select id="approveremail" name="approveremail" class="form-control"></select>
    </div>
    {if $sanOptionConfigId gt 0}
        <label for="san">
            SANs (separated by newlines)
        </label>
        <textarea id="san" name="san" class="form-control">{$csrData['san']}</textarea>
    {/if}

    {if $sanOptionWildcardConfigId gt 0}
        <label for="wildcardsan">
            Wildcard SANs (separated by newlines)
        </label>
        <textarea id="wildcardsan" name="wildcardsan" class="form-control">{$csrData['wildcardSan']}</textarea>
    {/if}

    <h3>
        Approver
    </h3>
    <div class="form-group">
        <fieldset class="pt-3">
            <label for="inputFirstName">First Name</label>
            <input type="text" class="form-control" name="firstname" id="inputFirstName" value="{$csrData['firstName']}">

            <label for="inputLastName">Last Name</label>
            <input type="text" class="form-control" name="lastname" id="inputLastName" value="{$csrData['lastName']}">

            <label for="inputOrgName">Organization Name</label>
            <input type="text" class="form-control" name="orgname" id="inputOrgName" value="{$csrData['organization']}">

            <label for="inputJobTitle">Job Title</label>
            <input type="text" class="form-control" name="jobtitle" id="inputJobTitle" value="{$csrData['jobTitle']}">

            <label for="inputEmail">Email Address</label>
            <input type="text" class="form-control" name="email" id="inputEmail" value="{$csrData['email']}">

            <label for="inputAddress1">Address 1</label>
            <input type="text" class="form-control" name="address1" id="inputAddress1" value="{$csrData['addressLine']}">

            <label class="" for="inputCity">City</label>
            <input type="text" class="form-control" name="city" id="inputCity" value="{$csrData['locality']}">

            <label for="inputState">State/Region</label>
            <input type="text" class="form-control" name="state" id="inputState" value="{$csrData['state']}">

            <label for="inputPostcode">Zip Code</label>
            <input type="text" class="form-control" name="postcode" id="inputPostcode" value="{$csrData['postalCode']}">

            <label for="inputCountry">Country</label>
            <select id="inputCountry" name="country" class="form-control">
                {foreach $countries as $value => $name}
                    <option value="{$value}" {if $value == $csrData['country']}selected=''{/if}>
                        {$name}
                    </option>
                {/foreach}
            </select>
            <label for="voice">Phone Number</label>
            <input type="text" class="form-control" name="voice" id="voice" value="{$csrData['phoneNumber']}">
        </fieldset>
    </div>
</div>
<hr/>
<script>
    let sanInput

    function debounce(func, timeout = 100){
        let timer;
        return () => {
            clearTimeout(timer);
            timer = setTimeout(() => func(), timeout);
        };
    }

    function initPreOrderFill() {
        console.log("init");
        const sanOptionConfigId = {$sanOptionConfigId};
        const includedSan = {$includedSan}
        const includedWildcardSan = {$includedSanWildcard}
        const sanOptionWildcardConfigId = {$sanOptionWildcardConfigId}

        {literal}
        sanInput = $(`#inputConfigOption${sanOptionConfigId}`);
        const sanSlider = sanInput.data('ionRangeSlider');
        const wildcardSanSlider = $(`#inputConfigOption${sanOptionWildcardConfigId}`).data('ionRangeSlider');


        $('input[name="CN"]').on('change', e => {
            const token = $('input[name="token"]').val();
            const commonName = e.target.value;
            let serviceUrl = 'index.php?action=approverEmails&json=1' +
                '&commonName=' + commonName +
                '&token=' + token;
            $.ajax({
                url: serviceUrl,
                type: "GET",
                json: 1,
                success: function (ret) {
                    const data = JSON.parse(ret);
                    if (data.success) {
                        data.emails[commonName].forEach(email =>
                            $('select[name="approveremail"]')
                                .append(`<option value ='${email}'>${email}</option>`)
                        )
                    }
                },
                error: function (e) {
                    console.error(e);
                },
            });
        });

        $('select[name="dcv"]').on('change', e => {
            if (e.target.value === 'EMAIL') {
                $('#approveremails').show();
            } else {
                $('#approveremails').hide();
            }
        })

        $('textarea[name="san"]').on('change', e => {
            const sanDomains = e.target.value.split('\n').filter(Boolean);
            const count = Math.max(sanDomains.length - includedSan, 0)
            sanSlider.update({
                from: count,
                from_min: count,
                from_max: count
            });
            recalctotals();
        }).change();

        $('textarea[name="wildcardsan"]').on('change', e => {
            const wildcardSanDomains = e.target.value.split('\n').filter(Boolean);
            const count = Math.max(wildcardSanDomains.length - includedWildcardSan, 0)
            wildcardSanSlider.update({
                from: count,
                from_min: count,
                from_max: count
            });
            recalctotals();
        }).change();

    }

    $(function () {
        initPreOrderFill();

        const mutationObserver = new MutationObserver(function (e) {
            initPreOrderFill();
        });

        $('#productConfigurableOptions').each(function () {
            mutationObserver.observe(this, {childList: true});
        })
    });
    {/literal}
</script>