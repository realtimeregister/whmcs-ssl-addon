{if $formError}
    <div class="col-lg-12">
        <div class="note note-danger">
            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
            <p><strong>{$formError}</strong></p>
        </div>
    </div>
    <div class="clearfix"></div>
{/if}  

<div class="panel panel-default">
    <div class="panel-heading">{$MGLANG->T('singleProductCreator')}</div>
    <div class="panel-body">

        <form action="" method="post" class="form-horizontal">

            <div class="form-group">
                <label class="control-label col-sm-2">{$MGLANG->T('realtimeRegisterSSLProduct')}</label>
                <div class="col-sm-10">
                    <select name="configoption1" class="form-control" id="api_product">
                        {foreach from=$apiProducts item=product}
                            <option data-peroids="{','|implode:$product->getPeriods()}" data-is_multidomain="{$product->san_enabled}" value="{$product->product}">{$product->product}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-sm-2">{$MGLANG->T('productName')}</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="name" value="" required/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2">{$MGLANG->T('issued_ssl_message')}</label>
                <div class="col-sm-10">
                    <textarea class="form-control" name="issued_ssl_message"></textarea>
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-sm-2">{$MGLANG->T('customguide')}</label>
                <div class="col-sm-10">
                    <textarea class="form-control" name="custom_guide"></textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2">{$MGLANG->T('productGroup')}</label>
                <div class="col-sm-10">
                    <select name="gid" class="form-control">
                        {foreach from=$productGroups item=productGroup}
                            <option value="{$productGroup->id}">{$productGroup->name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
                    
            <div class="form-group">
                <label class="control-label col-sm-2">{$MGLANG->T('autoSetup')}</label>
                <div class="col-sm-10">
                    <select name="autosetup" class="form-control">
                        <option value="order">{$MGLANG->T('autoSetupOrder')}</option>  
                        <option value="payment">{$MGLANG->T('autoSetupPayment')}</option> 
                        <option value="on">{$MGLANG->T('autoSetupOn')}</option> 
                        <option value="" selected="" >{$MGLANG->T('autoSetupOff')}</option> 
                    </select>
                </div>
            </div>

            <input type="submit" name="createSingle" class="btn btn-success" value="{$MGLANG->T('saveSingle')}" />

        </form>

    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">{$MGLANG->T('multipleProductCreator')}</div>
    <div class="panel-body">
        <form action="" method="post" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-sm-2">{$MGLANG->T('productGroup')}</label>
                <div class="col-sm-10">
                    <select name="gid" class="form-control">
                        {foreach from=$productGroups item=productGroup}
                            <option value="{$productGroup->id}">{$productGroup->name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <input type="submit" name="createMass" class="btn btn-success" value="{$MGLANG->T('saveMultiple')}" />  
        </form>
    </div>
</div>

<script>
    {literal}
        $(document).ready(function () {
        var options = $('select#api_product option');
            var arr = options.map(function(_, o) {
                return {
                    t: $(o).text(),
                    v: o.value
                };
            }).get();
            arr.sort(function(o1, o2) {
                return o1.t > o2.t ? 1 : o1.t < o2.t ? -1 : 0;
            });
            options.each(function(i, o) {
                o.value = arr[i].v;
                $(o).text(arr[i].t);
            });
            $(".pricingtgl").each(function (no, item) {
                if ($(item).is(":checked")) {

                } else {
                    var cycle = $(item).attr("cycle");
                    var currency = $(item).attr("currency");

                    $("#pricing_" + currency + "_" + cycle).val("-1.00").hide();
                    $("#setup_" + currency + "_" + cycle).hide();
                }
            });


            $(".pricingtgl").click(function () {
                var cycle = $(this).attr("cycle");
                var currency = $(this).attr("currency");
                var pricingId = $(this).data('pricing-id');

                console.log($(this).is(":checked"));
                console.log("#pricing_" + currency + "_" + cycle + "_" + pricingId);

                if ($(this).is(":checked")) {
                    $("#pricing_" + currency + "_" + cycle).val("0.00").show();
                    $("#setup_" + currency + "_" + cycle).show();
                } else {
                    $("#pricing_" + currency + "_" + cycle).val("-1.00").hide();
                    $("#setup_" + currency + "_" + cycle).hide();
                }
            });

        });

        $(document).ready(function () {

            var enableSansInput = $('#enable_sans'),
                    sansCountInput = $('#included_sans'),
                    apiProductInput = $('#api_product'),
                    monthsInput = $('#months');

            function configureSansCountInput(element) {
                if (element.is(":checked")) {
                    sansCountInput.val(0).attr('disabled', false);
                } else {
                    sansCountInput.val(0).attr('disabled', true);
                }
            }

            function configureSansEnableInput(element) {
                if (element.find(":selected").data('is_multidomain') === 0) {
                    enableSansInput.attr('checked', false).attr('disabled', true).trigger('change');
                } else {
                    enableSansInput.attr('checked', true).attr('disabled', false).trigger('change');
                }
            }

            function getOptionInputHtml(conf) {
                return '<option value="' + conf.value + '">' + conf.name + '</option>';
            }

            function buildMonthsInput(element) {
                var peroids = element.find(":selected").data('peroids'), options = '';

                if (typeof peroids === 'undefined') {
                    return;
                }

                if (typeof peroids === 'number') {
                    peroids = peroids.toString();
                }

                peroids = peroids.split(',');                
                for (var i = 0; i < peroids.length; i++) {
                    options = options + getOptionInputHtml({value: peroids[i], name: peroids[i]});
                }
                monthsInput.html(options);                
                changePrincingOptions(peroids)
                
            }
            function changePrincingOptions(peroids){
                var AllPeriodsInMonths = [ "3" , "6", "12", "24", "36"];
                for (var i = 0; i < AllPeriodsInMonths.length; i++) {
                    if(jQuery.inArray( AllPeriodsInMonths[i], peroids ) == -1) {
                        $('.pricing_' + AllPeriodsInMonths[i]).hide();
                    } else {
                         $('.pricing_' + AllPeriodsInMonths[i]).show();
                    }
                }
            }

            buildMonthsInput(apiProductInput);
            configureSansEnableInput(apiProductInput);
            configureSansCountInput(enableSansInput);

            enableSansInput.on('change', function () {
                configureSansCountInput($(this));
            });

            apiProductInput.on('change', function () {
                configureSansEnableInput($(this));
                buildMonthsInput($(this));
            });
            $('#api_product').bind('change', function(){
                var options = $('#months option');
                var periods = $.map(options ,function(option) {
                    return option.value;
                });
                
                changePrincingOptions(periods)
            })
        });
    {/literal}
</script>
