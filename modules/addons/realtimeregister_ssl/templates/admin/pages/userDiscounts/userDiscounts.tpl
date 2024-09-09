
{literal}
    <script>
        var pleaseSelectOnePlaceholder = '{/literal}{$MGLANG->T('modal','pleaseSelecetOnePlaceholder')}{literal}';
        var pleaseSelectProductFirst = '{/literal}{$MGLANG->T('modal','pleaseSelectProductFirst')}{literal}';
        var noDataAvailable = '{/literal}{$MGLANG->T('modal','noDataAvailable')}{literal}';
        var pleaseSelectClientFirst = '{/literal}{$MGLANG->T('modal','selectClientFirstPlaceholder')}{literal}';
        var noClientAvaialblePlaceholder = '{/literal}{$MGLANG->T('modal','noClientAvaialblePlaceholder')}{literal}';
        var noProductAvaialblePlaceholder = '{/literal}{$MGLANG->T('modal','noProductAvaialblePlaceholder')}{literal}';

    </script>
{/literal}
<style>
    #rulesTable
    td:nth-child(4) span,
    td:nth-child(5) span,
    td:nth-child(6) span,
    td:nth-child(7) span,
    td:nth-child(8) span,
    td:nth-child(9) span
    {
        margin-left: 25%;
    }
    table.dataTable .sorting::after, .sorting_asc::after, .sorting_desc::after
    {
        content: unset !important;
    }
</style>
<div class="box light">
    <div class="row">
        <div class="col-lg-12" id="mg-home-content" >
            <legend>{$MGLANG->T('title')}</legend>
            <div class="row">
                <div class="col-lg-2">
                    <button type="button" class="btn btn-success btn-inverse" id="addUserDiscountRule">{$MGLANG->T('addNewDiscountRule')}</button>
                </div>
            </div>
            <div id="rulesTable">
                <table width="100%" class="table table-striped" >
                    <colgroup>
                        <col style="width: 12%"/>
                        <col style="width: 12%"/>
                        <col style="width: 7%"/>
                        <col style="width: 15%"/>
                        <col style="width: 15%"/>
                        <col style="width: 15%"/>
                        <col style="width: 15%"/>
                        <col style="width: 7%"/>
                    </colgroup>
                    <thead>
                    <th>{$MGLANG->T('table', 'client')}</th>
                    <th>{$MGLANG->T('table', 'product')}</th>
                    <th>{$MGLANG->T('table', 'discount')}</th>
                    <th>{$MGLANG->T('table', 'monthly/onetime')}</th>
                    <th>{$MGLANG->T('table', 'annually')}</th>
                    <th>{$MGLANG->T('table', 'biennially')}</th>
                    <th>{$MGLANG->T('table', 'triennially')}</th>
                    <th>{$MGLANG->T('table', 'actions')}</th>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!--   Add User Discount Rule Modal -->
<form data-toggle="validator" role="form" id="MGAddDiscountForm">
    <div class="modal fade bs-example-modal-lg" id="MGAddDiscount" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title">{$MGLANG->T('modal', 'addDiscountRule')} <strong></strong></h4>
                </div>
                <div class="modal-loader" style="display:none;"></div>
                <div class="modal-body">
                    <div class="modal-alerts">
                        <div style="display:none;" data-prototype="error" id="errorModal">
                            <div class="note note-danger">
                                <strong></strong>
                                <a style="display:none;" class="errorID" href=""></a>
                            </div>
                        </div>
                        <div style="display:none;" data-prototype="success" id="successModal">
                            <div class="note note-success">
                                <strong></strong>
                            </div>
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-client" class="col-sm-2 control-label">{$MGLANG->T('modal','client')}</label>
                            <div class="col-sm-9">
                                <select name="client" class="form-control" id="mg-access-client" placeholder="" required="">
                                    {if $clients|@count == 0}
                                        <option value="" disabled selected>{$MGLANG->T('modal','noClientAvailable')}</option>
                                    {else}
                                        <option value="" disabled selected>{$MGLANG->T('modal','pleaseSelecetOnePlaceholder')}</option>
                                        {foreach from=$clients item=client}
                                            <option value="{$client.id}">#{$client.id} {$client.name}</option>
                                        {/foreach}
                                    {/if}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-product" class="col-sm-2 control-label">{$MGLANG->T('modal','product')}</label>
                            <div class="col-sm-9">
                                <select name="product" class="form-control" id="mg-access-product" placeholder="" required="">
                                    <option value='' disabled="" selected="">{$MGLANG->T('modal','selectClientFirstPlaceholder')}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-product" class="col-sm-2 control-label">{$MGLANG->T('modal','productPrice')}</label>
                            <div class="col-sm-9">
                                <table id="product_price" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>{$MGLANG->T('modal', 'monthly/onetime')}</th>
                                            <th>{$MGLANG->T('modal', 'annually')}</th>
                                            <th>{$MGLANG->T('modal', 'biennially')}</th>
                                            <th>{$MGLANG->T('modal', 'triennially')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8" align="center">{$MGLANG->T('modal','pleaseSelectProductFirst')}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-discount" class="col-sm-2 control-label">{$MGLANG->T('modal','discount')}</label>
                            <div class="col-sm-9">
                                <input name="discount" value="" class="form-control" id="mg-access-discount" placeholder=""  type="text" required="" pattern="\d*">
                            </div>
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-product" class="col-sm-2 control-label">{$MGLANG->T('modal','productPriceWithDiscount')}</label>
                            <div class="col-sm-9">
                                <table id="product_price_with_discount" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>{$MGLANG->T('modal', 'monthly/onetime')}</th>
                                            <th>{$MGLANG->T('modal', 'annually')}</th>
                                            <th>{$MGLANG->T('modal', 'biennially')}</th>
                                            <th>{$MGLANG->T('modal', 'triennially')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8" align="center">{$MGLANG->T('modal','pleaseSelectProductFirst')}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="margin-top:  5px;">
                    <button type="button" class="btn btn-success btn-inverse" id="addNewUserDiscountRule">{$MGLANG->T('modal','add')}</button>
                    <button type="button" class="btn btn-default btn-inverse" data-dismiss="modal">{$MGLANG->T('modal','close')}</button>
                </div>
            </div>
        </div>
    </div>
</form>
<!--   Edit User Discount Rule Modal -->
<form data-toggle="validator" role="form" id="MGEditDiscountForm">
    <div class="modal fade bs-example-modal-lg" id="MGEditDiscount" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                    <h4 class="modal-title">{$MGLANG->T('modal', 'editDiscountRule')} <strong></strong></h4>
                </div>
                <div class="modal-loader" style="display:none;"></div>
                <div class="modal-body">
                    <div class="modal-alerts">
                        <div style="display:none;" data-prototype="error" id="errorModal">
                            <div class="note note-danger">
                                <strong></strong>
                                <a style="display:none;" class="errorID" href=""></a>
                            </div>
                        </div>
                        <div style="display:none;" data-prototype="success" id="successModal">
                            <div class="note note-success">
                                <strong></strong>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" value="" name="rule_id" />
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-client" class="col-sm-2 control-label">{$MGLANG->T('modal','client')}</label>
                            <div class="col-sm-9">
                                <select name="client" class="form-control" id="mg-access-client" placeholder="" required="" readonly="">
                                    <option value="" disabled selected>{$MGLANG->T('modal','noClientAvailable')}</option>
                                </select>
                            </div>``
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-product" class="col-sm-2 control-label">{$MGLANG->T('modal','product')}</label>
                            <div class="col-sm-9">
                                <select name="product" class="form-control" id="mg-access-product" placeholder="" required="" readonly="">
                                    <option value="" disabled selected>{$MGLANG->T('modal','noProductAvailable')}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-product" class="col-sm-2 control-label">{$MGLANG->T('modal','productPrice')}</label>
                            <div class="col-sm-9">
                                <table id="product_price" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>{$MGLANG->T('modal', 'monthly/onetime')}</th>
                                            <th>{$MGLANG->T('modal', 'annually')}</th>
                                            <th>{$MGLANG->T('modal', 'biennially')}</th>
                                            <th>{$MGLANG->T('modal', 'triennially')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8" align="center">{$MGLANG->T('modal','pleaseSelectProductFirst')}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-edit-discount" class="col-sm-2 control-label">{$MGLANG->T('modal','discount')}</label>
                            <div class="col-sm-9">
                                <input name="discount" value="" class="form-control" id="mg-edit-discount" placeholder=""  type="text" required="" pattern="\d*">
                            </div>
                        </div>
                    </div>
                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="mg-access-product" class="col-sm-2 control-label">{$MGLANG->T('modal','productPriceWithDiscount')}</label>
                            <div class="col-sm-9">
                                <table id="product_price_with_discount" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>{$MGLANG->T('modal', 'monthly/onetime')}</th>
                                            <th>{$MGLANG->T('modal', 'annually')}</th>
                                            <th>{$MGLANG->T('modal', 'biennially')}</th>
                                            <th>{$MGLANG->T('modal', 'triennially')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="8" align="center">{$MGLANG->T('modal','pleaseSelectProductFirst')}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer" style="margin-top:  5px;">
                    <button type="button" class="btn btn-primary btn-inverse" id="updateUserDiscountRule">{$MGLANG->T('modal','edit')}</button>
                    <button type="button" class="btn btn-default btn-inverse" data-dismiss="modal">{$MGLANG->T('modal','close')}</button>
                </div>
            </div>
        </div>
    </div>
</form>
<!-- Remove Rule Modal  -->
<div class="modal fade bs-example-modal-lg" id="MGRuleRemove" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">{$MGLANG->T('modal','removeRule')}</h4>
            </div>
            <div class="modal-body">
                <input type='hidden' name='rule_id'/>
                <h4 class="text-center">{$MGLANG->T('modal','removeRuleInfo')} <b id="MGremoveInformation"></b></h4>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger btn-inverse" id="removeRuleButton" data-dismiss="modal">{$MGLANG->T('modal','remove')}</button>
                <button type="button" class="btn btn-default btn-inverse" data-dismiss="modal">{$MGLANG->T('modal','close')}</button>
            </div>
        </div>
    </div>
</div>


<script>
    {literal}
            function removeErrorStyle(modal)
            {
                $(modal).find('.form-group').removeClass('has-error');
                cleanModalMessage(modal);
            }
            function cleanModalMessage(modal) {
                $(modal).find('#successModal').attr('style', 'display:none;');
                $(modal).find('#errorModal').attr('style', 'display:none;');
            }
            function openModal(modal) {
                //clear fields
                $(modal).find('input,select').val('');
                //remove error styling
                removeErrorStyle($(modal));
                //open modal
                $(modal).modal();
            }
            function errorModal(info, modal) {
                cleanModalMessage(modal);
                $(modal).find('#errorModal').removeAttr('style');
                $(modal).find('#errorModal').find('strong').text(info);
            }
            function loadAvailalbleProducts(select)
            {
                var client_id = select.val();
                var modal = select.parents('.modal');

                JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&mg-page=userDiscounts', 'POST');
                JSONParser.request('loadAvailableProducts', {client_id: client_id}, function (data) {
                    if (data.products) {
                        var options = '<option disabled="" selected="" value="">' + pleaseSelectOnePlaceholder + '</option>';
                        data.products.forEach(function (product) {
                            options += '<option value="' + product.id + '">#' + product.id + ' ' + product.name + '</option>'
                        });

                        $('select[name="product"]').html(options);
                    } else {
                        errorModal(data.error, modal);
                    }
                });
            }

            function calculateNewPrice(price, multiplier)
            {
                if (price === '-')
                    return price;

                price = parseFloat(price);
                multiplier = (100 - parseFloat(multiplier)) / 100

                return (price * multiplier).toFixed(2);
            }

            function loadProductPricing(select)
            {
                var product_id = select.val();
                var modal = select.parents('.modal');
                var discount = parseFloat($(modal).find('input[name="discount"]').val());

                if (isNaN(discount))
                    discount = 0;

                JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&mg-page=userDiscounts', 'POST');
                JSONParser.request('loadProductPricing', {product_id: product_id}, function (data) {
                    if (data.pricings) {
                        var rows = generatePricingstableRows(data, discount);

                        $(modal).find('#product_price tbody').html(rows['rowsPriceHtml']);
                        $(modal).find('#product_price_with_discount tbody').html(rows['rowsWithPriceDiscountHtml']);
                    } else {
                        errorModal(data.error, modal);
                    }
                });
            }

            function recalculateProductPriceWithDiscount(input)
            {
                const modal = input.parents('.modal');
                let discount = parseFloat(input.val());
                const product_id = $(modal.find('select[name="product"]')).val();

                if (!product_id) {
                    return false;
                }
                if (isNaN(discount)) {
                    discount = 0;
                }


                JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&mg-page=userDiscounts', 'POST');
                JSONParser.request('loadProductPricing', { product_id }, function (data) {
                    if (data.pricings) {
                        var rowsWithPriceDiscountHtml = generatePricingstableRows(data, discount)['rowsWithPriceDiscountHtml'];

                        $(modal).find('#product_price_with_discount tbody').html(rowsWithPriceDiscountHtml);
                    } else {
                        errorModal(data.error, modal);
                    }
                });


            }

            function addNewDiscountRule(input)
            {
                var modal = input.parents('.modal');

                var client_id = $(modal).find('select[name="client"]').val();
                var product_id = $(modal).find('select[name="product"]').val();
                var discount = $(modal).find('input[name="discount"]').val();

                if(discount === '' || product_id === '' || product_id == null || client_id === '' || discount === '0')
                {
                    if(product_id == null)
                    {
                        $(modal).find('select[name="product"]').parents('.form-group').addClass('has-error');
                    }
                    if(discount === '0')
                    {
                        $(modal).find('input[name="discount"]').parents('.form-group').addClass('has-error');
                    }
                    return false;
                }

                JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&mg-page=userDiscounts', 'POST');
                JSONParser.request('addNewDiscountRule', {client_id: client_id, product_id: product_id, discount: discount}, function (data) {
                    if (data.success) {
                        $('#rulesTable table').DataTable().ajax.reload();
                        $(modal).modal('hide');
                    } else {
                        errorModal(data.error, modal);
                    }
                });
            }

            function generatePricingstableRows(data, discount)
            {
                const rows = {};
                rows['rowsPriceHtml'] = '';
                rows['rowsWithPriceDiscountHtml'] = '';

                if (data.pricings.length > 0)
                {
                    data.pricings.forEach(function (pricing) {
                        rows['rowsPriceHtml'] += '<tr><td align="center">' + pricing.code + '</td>' +
                            '<td align="center">' + pricing.monthly + '</td>' +
                            '<td align="center">' + pricing.annually + '</td>' +
                            '<td align="center">' + pricing.biennially + '</td>' +
                            '<td align="center">' + pricing.triennially + '</td></tr>'
                        rows['rowsWithPriceDiscountHtml'] += '<tr><td align="center">' + pricing.code + '</td>' +
                            '<td align="center">' + calculateNewPrice(pricing.monthly, discount) + '</td>' +
                            '<td align="center">' + calculateNewPrice(pricing.annually, discount) + '</td>' +
                            '<td align="center">' + calculateNewPrice(pricing.biennially, discount) + '</td>' +
                            '<td align="center">' + calculateNewPrice(pricing.triennially, discount) + '</td></tr>'
                    });
                } else
                {
                    rows['rowsPriceHtml'] = rows['rowsWithPriceDiscountHtml'] = '<tr><td colspan="8" align="center">' + noDataAvailable + '</td></tr>'
                }

                return rows;
            }

            function removeDiscountRule(button)
            {
                var modal = button.parents('.modal');

                var rule_id = modal.find('input[name="rule_id"]').val();

                JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&mg-page=userDiscounts', 'POST');
                JSONParser.request('removeDiscountRule', {rule_id: rule_id}, function (data) {
                    if (data.success) {
                        $('#rulesTable table').DataTable().ajax.reload();
                        $(modal).modal('hide');
                    } else {
                        errorModal(data.error, modal);
                    }
                });
            }

            function updateDiscountRule(button)
            {
                var modal = button.parents('.modal');

                var rule_id = modal.find('input[name="rule_id"]').val();
                var discount = modal.find('input[name="discount"]').val();

                if (discount === '' || discount === '0')
                {
                    $(modal).find('input[name="discount"]').parents('.form-group').addClass('has-error');
                    return false;
                }

                JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&mg-page=userDiscounts', 'POST');
                JSONParser.request('updateDiscountRule', { rule_id, discount }, function (data) {
                    if (data.success) {
                        $('#rulesTable table').DataTable().ajax.reload();
                        $(modal).modal('hide');
                    } else {
                        errorModal(data.msg, modal);
                    }
                });
            }

            function initDatatable()
            {
                $('#rulesTable table').DataTable({
                    "destroy": true,
                    "responsive": true,
                    "lengthChange": false,
                    "searching": true,
                    "processing": false,
                    "order": [[0, "asc"]],
                    "bInfo": false,
                    ajax: function (data, callback, settings) {
                        var filter = {
                            //    serverID: $('#pm-filters-server').val(),
                        };
                        JSONParser.request(
                                'getDiscountRules'
                                , {
                                    json: true,
                                    'mg-page': 'userDiscounts'
                                }
                        , function (data) {
                            callback(data);
                        }
                        );
                    },
                    "aoColumns": [
                        {'sType': 'natural', "bVisible": true, "responsivePriority": 1},
                        {'sType': 'natural', "bVisible": true, "responsivePriority": 2},
                        {'sType': 'natural', "bVisible": true, "responsivePriority": 3},
                        {'sType': 'natural', "bVisible": true, 'bSortable': false, "responsivePriority": 4},
                        {'sType': 'natural', "bVisible": true, 'bSortable': false, "responsivePriority": 5},
                        {'sType': 'natural', "bVisible": true, 'bSortable': false, "responsivePriority": 6},
                        {'sType': 'natural', "bVisible": true, 'bSortable': false, "responsivePriority": 7},
                        {'sType': 'natural', 'bVisible': true, 'bSortable': false, 'bSearchable': false, "responsivePriority": 0},
                    ]
                });
            }
            $(document).ready(function () {

                initDatatable();

                //Add New User Discount Rule Modal
                $(document).on('click', '#addUserDiscountRule', function (e) {
                    var modal = $("#MGAddDiscount");
                    openModal(modal)
                    var optionHTML = '<option value="" selected="" disabled="">' + pleaseSelectClientFirst + '</option>'
                    $(modal).find('select[name="product"]').html(optionHTML)
                    var rowsPriceHtml = '<tr><td colspan="8" align="center">' + pleaseSelectProductFirst + '</td></tr>'
                    $(modal).find('#product_price_with_discount tbody, #product_price tbody').html(rowsPriceHtml);
                    //set default fields values
                    //$(modal).find('#file_label').text(noFileSelectedPlaceholder)

                });
                $(document).on('change', "select[name='client']", function () {
                    console.log("loading products");
                    loadAvailalbleProducts($(this))
                });

                $(document).on('change', "select[name='product']", function () {
                    loadProductPricing($(this))
                });

                $(document).on('keyup', "input[name='discount']", function () {
                    recalculateProductPriceWithDiscount($(this))
                });

                $(document).on('click', "#addNewUserDiscountRule", function () {
                    addNewDiscountRule($(this))
                });

                $(document).on('click', '.deleteItem', function () {
                    var modal = $("#MGRuleRemove");
                    openModal(modal);
                    $(modal).find('input[name="rule_id"]').val($(this).data('id'));
                });

                $(document).on('click', '#removeRuleButton', function () {
                    removeDiscountRule($(this));
                });

                $(document).on('click', '.editItem', function () {
                    var modal = $("#MGEditDiscount");

                    removeErrorStyle($(modal));
                    var rule_id = $(this).data('id');

                    modal.find('input[name="rule_id"]').val($(this).data('id'));

                    JSONParser.create('addonmodules.php?module=realtimeregister_ssl&json=1&mg-page=userDiscounts', 'POST');
                    JSONParser.request('getSingleDiscountRule', {rule_id: rule_id}, function (data) {
                        if (data) {

                            console.log(data);

                            const clientOptionHTML = '<option value="" selected="">#' + data.client.id + ' ' + data.client.name + '</option>';
                            modal.find('select[name="client"]').html(clientOptionHTML);

                            const productOptionHTML = '<option value="' + data.product.id + '" selected="">#' + data.product.id + ' ' + data.product.name + '</option>';
                            modal.find('select[name="product"]').html(productOptionHTML);

                            modal.find('input[name="discount"]').val(data.discount);

                            const pricingRows = generatePricingstableRows(data, data.discount);

                            $(modal).find('#product_price tbody').html(pricingRows['rowsPriceHtml']);
                            $(modal).find('#product_price_with_discount tbody').html(pricingRows['rowsWithPriceDiscountHtml']);

                            modal.modal();
                        } else {
                            errorModal(data.error, modal);
                        }
                    });
                });

                //updateUserDiscountRule
                $(document).on('click', '#updateUserDiscountRule', function () {
                    updateDiscountRule($(this));
                });
            });
    {/literal}
</script>

