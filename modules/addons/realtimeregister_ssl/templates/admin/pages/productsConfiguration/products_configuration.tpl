{if $formError}
    <div class="col-lg-12">
        <div class="note note-danger">
            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
            <p><strong>{$formError}</strong></p>
        </div>
    </div>
{/if}

<form action="" method="post" class="form-horizontal margin-bottom-15" style="display: none;" id="createConfOptionsForm">
    <input type="hidden" name="createConfOptions" value="yes">
    <input id="createConfOptionsFormId" type="hidden" name="productId" value="">
    <input id="createConfOptionsFormName" type="hidden" name="productName" value="">
</form>

<form action="" method="post" class="form-horizontal margin-bottom-15" style="display: none;" id="createConfOptionsFormWildcard">
    <input type="hidden" name="createConfOptionsWildcard" value="yes">
    <input id="createConfOptionsFormIdWildcard" type="hidden" name="productId" value="">
    <input id="createConfOptionsFormNameWildcard" type="hidden" name="productName" value="">
</form>

{if $products_count}
    <div class="panel panel-default">
        <div class="panel-body">

            <div class="button-container pull-right">
                <button type="button" class="btn btn-primary save-all-products">{$ADDONLANG->T('save_all_products')}</button>
            </div>

            <! --- start new form --->
            <form action="" method="post" class="form-horizontal margin-bottom-15" onsubmit="return confirm('{$ADDONLANG->T('areYouSureManyProducts')}');">

                <input type="hidden" name="many-products" value="1">

                <div style="padding:0 15px;">
                    <h2 style="margin-bottom:50px">{$ADDONLANG->T('setForManyProducts')}</h2>

                    <div class="form-group">
                        <label class="control-label col-sm-2">{$ADDONLANG->T('issued_ssl_message')}</label>
                        <div class="col-sm-10" style="padding:0;">
                            <textarea class="form-control addon-product-commission" name="issued_ssl_message"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                            <label class="control-label col-sm-2">{$ADDONLANG->T('customguide')}</label>
                            <div class="col-sm-10" style="padding:0;">
                                <textarea class="form-control addon-product-commission" name="custom_guide"></textarea>
                            </div>
                        </div>

                    <div class="row">

                        <div class="form-group">
                            <label class="control-label col-sm-2">{$ADDONLANG->T('autoSetup')}</label>
                            <div class="col-sm-10">
                                <select name="autosetup" class="form-control">
                                    <option value="donot">{$ADDONLANG->T('doNotAnything')}</option>
                                    <option value="order">{$ADDONLANG->T('autoSetupOrder')}</option>
                                    <option value="payment">{$ADDONLANG->T('autoSetupPayment')}</option>
                                    <option value="on">{$ADDONLANG->T('autoSetupOn')}</option>
                                    <option value="">{$ADDONLANG->T('autoSetupOff')}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-2">{$ADDONLANG->T('commission')}</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control addon-product-commission" name="configoption6" value="" pattern="\d*">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-2">{$ADDONLANG->T('statusEnabled')}</label>
                            <div class="col-sm-10" style="padding-top: 8px;">
                                <input class="form-check-input addon-js-pricing-auto-download" name="hidden" value="1" type="checkbox" style="margin-top: -46px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-2">{$ADDONLANG->T('priceAutoDownlaod')}</label>
                            <div class="col-sm-10" style="padding-top: 8px;">
                                <input class="form-check-input addon-js-pricing-auto-download" name="configoption5" value="1" type="checkbox" style="margin-top: -46px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="control-label col-sm-2">{$ADDONLANG->T('allOrSelectedProducts')}</label>
                            <div class="col-sm-10">
                                <select name="type" class="form-control">
                                    <option value="all">{$ADDONLANG->T('allProducts')}</option>
                                    <option value="selected">{$ADDONLANG->T('selectedProducts')}</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" >
                            <label class="control-label col-sm-2">{$ADDONLANG->T('selectProducts')}</label>
                            <div class="col-sm-10">
                                <select multiple name="products[]" class="form-control" disabled>
                                    {foreach $products as $product}
                                        <option value="{$product->id}">{$product->name}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>


                        <input type="submit" name="saveProduct" class="btn btn-success" value="{$ADDONLANG->T('save')}" />

                    </div>
                </div>
            </form>
            <! --- end new form --->

            {foreach from=$products item=product}
                <h3 class="col-sm-12" data-toggle="collapse" href="#collapse-price-{$product->id}" aria-expanded="false" role="button">{$product->configoption1}
                    <i class="fa fa-chevron-right pull-right"></i>
                    <i class="fa fa-chevron-down pull-right"></i>
                </h3>
                <form id="collapse-price-{$product->id}" action="" method="post" class="save-product-form form-horizontal margin-bottom-15 col-sm-10 collapse">
                    <table class="table table-condensed" id="product_configuration">
                        <tr class="product-container" data-product="{$product->id}">
                        <input type="hidden" name="product[{$product->id}][id]" value="{$product->id}"/>
                        <td>
                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('realtimeRegisterSSLProduct')}</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" value="{$product->configoption1}" disabled>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('productName')}</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="product[{$product->id}][name]" value="{$product->name}">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('issued_ssl_message')}</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" name="product[{$product->id}][issued_ssl_message]">{$product->configoption23}</textarea>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('customguide')}</label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" name="product[{$product->id}][custom_guide]">{$product->configoption24}</textarea>
                                </div>
                            </div>


                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('autoSetup')}</label>
                                <div class="col-sm-10">
                                    <select name="product[{$product->id}][autosetup]" class="form-control">
                                        <option value="order" {if $product->autosetup == 'order'}selected=""{/if}>{$ADDONLANG->T('autoSetupOrder')}</option>
                                        <option value="payment" {if $product->autosetup == 'payment'}selected=""{/if}>{$ADDONLANG->T('autoSetupPayment')}</option>
                                        <option value="on" {if $product->autosetup == 'on'}selected=""{/if}>{$ADDONLANG->T('autoSetupOn')}</option>
                                        <option value="" {if $product->autosetup == ''}selected=""{/if}>{$ADDONLANG->T('autoSetupOff')}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('months')}</label>
                                <div class="col-sm-10">
                                    <div  class="maxMonths {if $product->paytype == 'onetime'}hidden{/if}">{$product->apiConfig->peroids}</div>
                                    <input {if $product->paytype == 'onetime'}class="hidden" disabled=""{/if} type="hidden" name="product[{$product->id}][configoption2]" value="{$product->apiConfig->peroids}"></input>
                                    <select name="product[{$product->id}][configoption2]" class="form-control {if $product->paytype != 'onetime'}hidden{/if}" {if $product->paytype != 'onetime'}disabled=""{/if}>
                                        {foreach from=$product->apiConfig->availablePeriods item=period}
                                            <option {if $product->configoption2 == $period}selected{/if} value="{$period}">{$period}</option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>

                            {if $product->apiConfig->isSanEnabled}
                                <div class="form-group">
                                        <input type="checkbox" class="hidden" name="product[{$product->id}][configoption3]" value="on" style="margin-top: 10px;" {if $product->configoption3 === 'on'} checked {/if}{if !$product->apiConfig->isSanEnabled} disabled {/if}>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2">{$ADDONLANG->T('includedSans')}</label>
                                    <div class="col-sm-10">
                                        <input type="number" class="form-control" name="product[{$product->id}][configoption4]" value="{$product->configoption4}" {if !$product->apiConfig->isSanEnabled} disabled {/if}>
                                    </div>
                                </div>
                            {/if}

                            {if $product->apiConfig->isWildcardSanEnabled}
                                <div class="form-group">
                                    <input type="checkbox" class="hidden" name="product[{$product->id}][configoption13]" value="on" style="margin-top: 10px;" {if $product->configoption13 === 'on'} checked {/if}{if !$product->apiConfig->isWildcardSanEnabled} disabled {/if}>
                                </div>
                                <div class="form-group">
                                    <label class="control-label col-sm-2">{$ADDONLANG->T('includedSansWildcard')}</label>
                                    <div class="col-sm-10">
                                        <input type="number" class="form-control" name="product[{$product->id}][configoption8]" value="{$product->configoption8}" {if !$product->apiConfig->isWildcardSanEnabled} disabled {/if}>
                                    </div>
                                </div>
                            {/if}

                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('status')}</label>
                                <div class="col-sm-10">
                                    <div class="buttons-container">
                                        {if $product->hidden eq 0}
                                            <button type="button" data-product-id="{$product->id}" class="btn btn-danger disable-product">{$ADDONLANG->T('statusDisable')}</button>
                                        {else}
                                            <button type="button" data-product-id="{$product->id}" class="btn btn-success enable-product">{$ADDONLANG->T('statusEnable')}</button>
                                        {/if}
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('paymentType')}</label>
                                <div class="col-sm-10">
                                    <select name="product[{$product->id}][paytype]" class="form-control addon-js-pricing-select" data-id="{$product->id}">
                                        <option {if $product->paytype == 'free'}selected{/if} value="free">{$ADDONLANG->T('paymentTypeFree')}</option>
                                        <option {if $product->paytype == 'recurring'}selected{/if} value="recurring">{$ADDONLANG->T('paymentTypeRecurring')}</option>
                                        <option {if $product->paytype == 'onetime'}selected{/if} value="onetime">{$ADDONLANG->T('paymentTypeOneTime')}</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('commission')}</label>
                                <div class="col-sm-10">
                                    {if $product->configoption6}
                                        <input type="text" class="form-control addon-product-commission" name="product[{$product->id}][configoption6]" value="{math equation="x * y" x=$product->configoption6 y=100}" data-id="{$product->id}" {if $product->paytype == 'free'}readonly=""{/if} pattern="\d*"/>
                                    {else}
                                        <input type="text" class="form-control addon-product-commission" name="product[{$product->id}][configoption6]" value="" data-id="{$product->id}" {if $product->paytype == 'free'}readonly=""{/if} pattern="\d*"/>
                                    {/if}
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('priceAutoDownlaod')}</label>
                                <div class="col-sm-10" style="padding-top: 8px;">
                                    <input class="form-check-input addon-js-pricing-auto-download" name="product[{$product->id}][configoption5]" data-id="{$product->id}" value="1" {if $product->configoption5} checked="" {/if} {if $product->paytype == 'free'}readonly="" disabled=""{/if} type="checkbox" />
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-2">{$ADDONLANG->T('configurableOptionsPeriod')}</label>
                                <div class="col-sm-10">
                                    <a href="#" onclick="manageconfigoptions('{$product->confOptionPeriod->id}');return false;" class="btn btn-success">{$ADDONLANG->T('editPrices')}</a>
                                    <small>{$ADDONLANG->T('pricingInclude')}</small>
                                </div>
                            </div>

                            {if $product->apiConfig->isSanEnabled}
                                <div class="form-group">
                                    <label class="control-label col-sm-2">{$ADDONLANG->T('configurableOptions')}</label>
                                    <div class="col-sm-10">
                                        {foreach from=$product->confOption key=i item=confOption}
                                            <a href="#" onclick="manageconfigoptions('{$confOption->id}');return false;"
                                            class="btn btn-success">{$i + 1} {$ADDONLANG->T('editYears')}</a>
                                        {/foreach}
                                        <small>{$ADDONLANG->T('pricingInclude')}</small>
                                    </div>
                                </div>
                            {/if}

                            {if $product->apiConfig->isWildcardSanEnabled}
                                <div class="form-group">
                                    <label class="control-label col-sm-2">{$ADDONLANG->T('configurableOptionsWildcard')}</label>
                                    <div class="col-sm-10">
                                        {foreach from=$product->confOptionWildcard key=i item=confOption}
                                            <a href="#" onclick="manageconfigoptions('{$confOption->id}');return false;"
                                            class="btn btn-success">{$i + 1} {$ADDONLANG->T('editYears')}</a>
                                        {/foreach}
                                        <small>{$ADDONLANG->T('pricingInclude')}</small>
                                    </div>
                                </div>
                            {/if}

                            <div class="form-group" id="addon-js-pricing-group-{$product->id}" {if $product->paytype == 'free'}style="display: none;"{/if}>
                                <label class="control-label col-sm-2">{$ADDONLANG->T('enableFor')}</label>
                                <div class="col-sm-10">

                                    <div class="product_prices">
                                        <table class="table">
                                            <tbody>
                                                <tr style="text-align:center;font-weight:bold">
                                                    <td></td>
                                                    <td class="prod-pricing-monthly-onetime">{$ADDONLANG->T('pricingMonthly')}</td>
                                                    {if in_array('12',$product->apiConfig->availablePeriods)}<td style="display: table-cell;" class="prod-pricing-recurring">{$ADDONLANG->T('pricingAnnually')}</td>{/if}
                                                    {if in_array('24',$product->apiConfig->availablePeriods)}<td style="display: table-cell;" class="prod-pricing-recurring">{$ADDONLANG->T('pricingBiennially')}</td>{/if}
                                                    {if in_array('36',$product->apiConfig->availablePeriods)}<td style="display: table-cell;" class="prod-pricing-recurring">{$ADDONLANG->T('pricingTriennially')}</td>{/if}
                                                </tr>
                                                {foreach from=$product->pricing item=pricing}
                                                    <tr style="text-align:center" bgcolor="#ffffff" currency="{$pricing->code}">
                                                        <td rowspan="2" bgcolor="#efefef"><b>{$pricing->code}</b></td>
                                                    </tr>
                                                    <tr style="text-align:center" bgcolor="#ffffff">
                                                        <td class="prod-pricing-monthly-onetime">
                                                            <input name="currency[{$pricing->pricing_id}][monthly]" class="pricingtgl" currency="{$pricing->code}" data-pricing-id="{$pricing->pricing_id}" cycle="monthly" type="checkbox" {if $pricing->monthly gte 0} checked="checked" {/if}>
                                                        </td>
                                                        {if in_array('12',$product->apiConfig->availablePeriods)}
                                                            <td style="display: table-cell;" class="prod-pricing-recurring">
                                                                <input name="currency[{$pricing->pricing_id}][annually]" class="pricingtgl" currency="{$pricing->code}" data-pricing-id="{$pricing->pricing_id}" cycle="annually" {if $pricing->annually gte 0} checked="checked" {/if} type="checkbox">
                                                            </td>
                                                        {/if}
                                                        {if in_array('24',$product->apiConfig->availablePeriods)}
                                                            <td style="display: table-cell;" class="prod-pricing-recurring">
                                                                <input name="currency[{$pricing->pricing_id}][biennially]" class="pricingtgl" data-pricing-id="{$pricing->pricing_id}" currency="{$pricing->code}" {if $pricing->biennially gte 0} checked="checked" {/if} cycle="biennially" type="checkbox">
                                                            </td>
                                                        {/if}
                                                        {if in_array('36',$product->apiConfig->availablePeriods)}
                                                            <td style="display: table-cell;" class="prod-pricing-recurring">
                                                                <input name="currency[{$pricing->pricing_id}][triennially]" class="pricingtgl" data-pricing-id="{$pricing->pricing_id}" currency="{$pricing->code}" cycle="triennially" {if $pricing->triennially gte 0} checked="checked" {/if} type="checkbox">
                                                            </td>
                                                        {/if}
                                                    </tr>
                                                {/foreach}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </td>
                        </tr>
                    </table>
                    <input type="submit" name="saveProduct" class="btn btn-success" value="{$ADDONLANG->T('save')}" />
                </form>
            {/foreach}
            <div class="button-container pull-right">
                <button type="button" class="btn btn-primary save-all-products">{$ADDONLANG->T('save_all_products')}</button>
            </div>
        </div>
    </div>
    <script>
        {literal}
            function manageconfigoptions(id) {
                window.open('configproductoptions.php?manageoptions=true&cid=' + id, 'configoptions', 'width=900,height=500,scrollbars=yes');
            }
            $(document).ready(function () {

                $('body').on('click','.save-all-products', function(){

                    $('#AddonLoader').show();

                    var promises = [];

                    $('.save-product-form').each( function(){

                        var dataForm = $(this).serialize();
                        var urlFrom = $(this).attr('action');

                        var request = $.ajax({
                            type: "POST",
                            url: urlFrom,
                            async: true,
                            data: {
                                'field': dataForm,
                                'ajax': '1',
                                'saveProduct': 'Save'
                            },
                            success: function(data)
                            {

                            }
                        });

                        promises.push(request);
                    });

                    $.when.apply(null, promises).done(function(){
                        $('#AddonAlerts div[data-prototype="success"] strong').text('{/literal}{$ADDONLANG->T('products_saved')}{literal}');
                        $('#AddonAlerts div[data-prototype="success"]').show();
                        $('#AddonLoader').hide();
                        $("html, body").animate({ scrollTop: 0 }, "slow");
                    });
                });

                $('body').on('change', 'select[name="type"]', function(){

                    var valtype = $(this).val();

                    if(valtype == 'all')
                    {
                        $('select[name="products[]"]').prop('disabled', true);
                    }
                    else
                    {
                        $('select[name="products[]"]').prop('disabled', false);
                    }

                });

                $('.addon-js-create-options').on('click', function () {
                    $('#createConfOptionsFormId').val($(this).data('id'));
                    $('#createConfOptionsFormName').val($(this).data('name'));
                    $('#createConfOptionsForm').submit();
                });


                $('.addon-js-create-options-wildcard').on('click', function () {
                    $('#createConfOptionsFormIdWildcard').val($(this).data('id'));
                    $('#createConfOptionsFormNameWildcard').val($(this).data('name'));
                    $('#createConfOptionsFormWildcard').submit();
                });

                jQuery('.buttons-container').on('click', '.disable-product', function () {
                    var productId = $(this).data('product-id');
                    button = $(this);
                    JSONParser.request('disableProduct', {productId: productId}, function (data) {
                        if (data.success) {
                            switchButtons('enToDis', button, productId);
                        } else {
                            switchButtons('disToEn', button, productId);
                        }
                    }, false);
                });


                jQuery('.buttons-container').on('click', '.enable-product', function () {
                    var productId = $(this).data('product-id');
                    button = $(this);
                    JSONParser.request('enableProduct', {productId: productId}, function (data) {
                        if (data.success) {
                            switchButtons('disToEn', button, productId);
                        } else {
                            switchButtons('enToDis', button, productId);
                        }
                    }, false);
                });


                function switchButtons(type, container, productId) {
                    if (type == 'enToDis') {
                        container.parent().html('<button type="button" data-product-id="' + productId + '" class="btn btn-danger disable-product">{/literal}{$ADDONLANG->T('statusDisable')}{literal}</button>');
                    } else {
                        container.parent().html('<button type="button" data-product-id="' + productId + '" class="btn btn-success enable-product">{/literal}{$ADDONLANG->T('statusEnable')}{literal}</button>');
                    }
                }

                function initFields() {
                    $(".pricingtgl").each(function (no, item) {

                        if ($(item).is(":checked")) {

                        } else {

                            var cycle = $(item).attr("cycle");
                            var currency = $(item).attr("currency");
                            var pricingId = $(item).data('pricing-id');

                            $("#pricing_" + currency + "_" + cycle + "_" + pricingId).val("-1.00").hide();
                            $("#pricing_commission_" + currency + "_" + cycle + "_" + pricingId).hide();
                            $("#setup_" + currency + "_" + cycle + "_" + pricingId).hide();
                        }
                    });
                }
                initFields();

                function disablePrices(element) {
                    element.attr("disabled", true);
                    element.addClass('disabled');
                    element.prop('checked', false);
                }

                function enablePrices(element) {
                    element.removeAttr('disabled')
                    element.removeClass('disabled');
                }

                function showOneTime(element, type) {
                    element.find("input[cycle='monthly']").removeClass('disabled');
                    element.find("input[cycle='monthly']").removeAttr('disabled');

                    if (element.find("input[cycle='monthly']").hasClass('monthly')) {
                        if (type !== 'free') {
                            $(element).find("input[cycle='monthly']").prop('checked', false);
                        }
                        element.find("input[cycle='monthly']").removeClass('monthly');
                    }
                    element.find("input[cycle='monthly']").addClass('onetime');
                }

                function hideOneTime(element, type) {
                    if (element.find("input[cycle='monthly']").hasClass('onetime') || type === null) {
                        $(element).find("input[cycle='monthly']").prop('checked', false);
                        element.find("input[cycle='monthly']").removeClass('onetime');
                    }

                    element.find("input[cycle='monthly']").addClass('monthly');
                }

                function setAsOneTime(select, type = null) {
                    var pc = select.closest('.product-container');
                    //disablePrices(pc.find("input[cycle='monthly']"));
                    showOneTime(pc, type);
                    disablePrices(pc.find("input[cycle='annually']"));
                    disablePrices(pc.find("input[cycle='biennially']"));
                    disablePrices(pc.find("input[cycle='triennially']"));
                    initFields();
                }

                function setAsNonOneTime(select, type = null) {
                    var pc = select.closest('.product-container');
                    //enablePrices(pc.find("input[cycle='monthly']"));
                    hideOneTime(pc, type);
                    enablePrices(pc.find("input[cycle='annually']"));
                    enablePrices(pc.find("input[cycle='biennially']"));
                    enablePrices(pc.find("input[cycle='semiannually']"));
                    enablePrices(pc.find("input[cycle='triennially']"));
                    initFields();
                }


                $(".pricingtgl").click(function () {
                    var cycle = $(this).attr("cycle");
                    var currency = $(this).attr("currency");
                    var pricingId = $(this).data('pricing-id');

                    if ($(this).is(":checked")) {
                        if ($('input[name="product[' + pricingId + '][configoption5]"]').is(':checked'))
                            $("#pricing_" + currency + "_" + cycle + "_" + pricingId).prop('readonly', true);
                        $("#pricing_" + currency + "_" + cycle + "_" + pricingId).val("0.00").show();
                        $("#pricing_commission_" + currency + "_" + cycle + "_" + pricingId).show();
                        $("#setup_" + currency + "_" + cycle + "_" + pricingId).show();
                    } else {
                        $("#pricing_" + currency + "_" + cycle + "_" + pricingId).val("-1.00").hide();
                        $("#pricing_commission_" + currency + "_" + cycle + "_" + pricingId).hide();
                        $("#setup_" + currency + "_" + cycle + "_" + pricingId).hide();
                    }
                });

                function showHidePricing(select) {
                    var productId = select.data('id');
                    var type = select.val();
                    if (type === 'free') {
                        setAsNonOneTime(select);
                        $('#addon-js-pricing-group-' + productId).hide();
                    } else if (type === 'onetime') {
                        setAsOneTime(select, type);
                        $('#addon-js-pricing-group-' + productId).show();
                    } else {
                        setAsNonOneTime(select, type);
                        $('#addon-js-pricing-group-' + productId).show();
                    }
                }

                function showHidePeriodSelection(select) {
                    var productId = select.data('id');
                    var type = select.val();
                    if (type === 'onetime') {
                        $(select).parents('td').find('.maxMonths').addClass('hidden');
                        $('input[name="product[' + productId + '][configoption2]"]').addClass('hidden').prop('disabled', true);
                        $('select[name="product[' + productId + '][configoption2]"]').removeClass('hidden').prop('disabled', false);
                    } else {
                        $(select).parents('td').find('.maxMonths').removeClass('hidden');
                        $('input[name="product[' + productId + '][configoption2]"]').removeClass('hidden').prop('disabled', false);
                        $('select[name="product[' + productId + '][configoption2]"]').addClass('hidden').prop('disabled', true);
                    }
                }

                function enableDisableCommission(select)
                {
                    var productId = select.data('id');
                    var type = select.val();
                    if (type === 'free')
                    {
                        $('input[name="product[' + productId + '][configoption6]"]').val('').prop('readonly', true);
                    } else
                    {
                        $('input[name="product[' + productId + '][configoption6]"]').prop('readonly', false);
                    }
                }

                function enableDisableAutoPriceUpdate(select)
                {
                    var productId = select.data('id');
                    var type = select.val();
                    if (type === 'free')
                    {
                        $('input[name="product[' + productId + '][configoption5]"]').prop('checked', false).prop('readonly', true).prop('disabled', true);
                    } else
                    {
                        $('input[name="product[' + productId + '][configoption5]"]').prop('readonly', false).prop('disabled', false);
                    }
                }

                function enableDisablePriceField(checkbox) {
                    var productId = checkbox.data('id');
                    var checked = false;
                    if (checkbox.is(":checked")) {
                        checked = true;
                    }

                    if (checked)
                    {
                        $('#addon-js-pricing-group-' + productId).find('input[id^="pricing_"]').prop('readonly', true);
                    } else
                    {
                        $('#addon-js-pricing-group-' + productId).find('input[id^="pricing_"]').prop('readonly', false);
                    }
                }
                function changePriceWithCommission(input) {
                    const commissionValue = parseFloat(input.val());
                    const checkboxes = input.parents('#product_configuration').find('.product_prices').find('.pricingtgl');
                    checkboxes.each(function (index) {
                        if ($(this).is(':checked')) {
                            const cycle = $(this).attr("cycle");
                            const currency = $(this).attr("currency");
                            const pricingId = $(this).data('pricing-id');
                            let price = parseFloat($("#pricing_" + currency + "_" + cycle + "_" + pricingId).val());
                            let priceWithCommission = price * commissionValue / 100 + price;

                            if(isNaN(price))
                                price = '0.00';
                            if (isNaN(priceWithCommission))
                                priceWithCommission = price;
                            $("#pricing_commission_" + currency + "_" + cycle + "_" + pricingId).val('').val(priceWithCommission.toFixed(2));
                        }
                    });
                }

                $('.addon-js-pricing-select').each(function () {
                    showHidePricing($(this));
                });

                $('.addon-js-pricing-select').on('change', function () {
                    showHidePricing($(this), true);
                    showHidePeriodSelection($(this));
                });

                $('.addon-js-pricing-select').on('change', function () {
                    showHidePricing($(this), true);
                    showHidePeriodSelection($(this));
                    enableDisableCommission($(this));
                    enableDisableAutoPriceUpdate($(this));
                });
                $('.addon-js-pricing-auto-download').on('change', function () {
                    enableDisablePriceField($(this))
                });
                $('.addon-product-commission').on('change keyup paste', function () {
                    changePriceWithCommission($(this))
                });
                $('input[id^="pricing_"]').on('change keyup paste', function () {
                    changePriceWithCommission($(this).parents('#product_configuration').find('.addon-product-commission'))
                });
            });
        {/literal}
    </script>
{else}
    <div class="alert alert-info">
        No products found - to add go to `Products Creator` page.
    </div>
{/if}
