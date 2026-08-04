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
    <div class="panel-heading">{$ADDONLANG->T('singleProductCreator')}</div>
    <div class="panel-body">

        <form action="" method="post" class="form-horizontal">

            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('realtimeRegisterSSLProduct')}</label>
                <div class="col-sm-10">
                    <select name="configoption1" class="form-control" id="api_product">
                        {foreach from=$apiProducts item=product}
                         {if !$product->isAcmeProduct()}
                            <option data-peroids="{','|implode:$product->getPeriods()}"
                                    data-is_multidomain="{$product->san_enabled}"
                                    value="{$product->product}">
                                {$product->product}
                            </option>
                        {/if}
                        {/foreach}
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('productName')}</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="name" value="" required/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('issued_ssl_message')}</label>
                <div class="col-sm-10">
                    <textarea class="form-control" name="issued_ssl_message"></textarea>
                </div>
            </div>
                
            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('customguide')}</label>
                <div class="col-sm-10">
                    <textarea class="form-control" name="custom_guide"></textarea>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('productGroup')}</label>
                <div class="col-sm-10">
                    <select name="gid" class="form-control">
                        {foreach from=$productGroups item=productGroup}
                            <option value="{$productGroup->id}">{$productGroup->name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
                    
            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('autoSetup')}</label>
                <div class="col-sm-10">
                    <select name="autosetup" class="form-control">
                        <option value="order">{$ADDONLANG->T('autoSetupOrder')}</option>
                        <option value="payment">{$ADDONLANG->T('autoSetupPayment')}</option>
                        <option value="on">{$ADDONLANG->T('autoSetupOn')}</option>
                        <option value="" selected="" >{$ADDONLANG->T('autoSetupOff')}</option>
                    </select>
                </div>
            </div>
            <input type="submit" name="createSingle" class="btn btn-success" value="{$ADDONLANG->T('saveSingle')}" />
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">{$ADDONLANG->T('singleAcmeProductCreator')}</div>
    <div class="panel-body">

        <form action="" method="post" class="form-horizontal">

            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('realtimeRegisterSSLProduct')}</label>
                <div class="col-sm-10">
                    <select name="configoption1" class="form-control" id="amce_api_product">
                        {foreach from=$apiProducts item=product}
                         {if $product->isAcmeProduct()}
                            <option data-peroids="{','|implode:$product->getPeriods()}"
                                    data-is_multidomain="{$product->san_enabled}"
                                    value="{$product->product}">
                                {$product->product}
                            </option>
                        {/if}
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('productName')}</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" name="name" value=""/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('productGroup')}</label>
                <div class="col-sm-10">
                    <select name="gid" class="form-control">
                        {foreach from=$productGroups item=productGroup}
                            <option value="{$productGroup->id}">{$productGroup->name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('autoSetup')}</label>
                <div class="col-sm-10">
                    <select name="autosetup" class="form-control">
                        <option value="order">{$ADDONLANG->T('autoSetupOrder')}</option>
                        <option value="payment">{$ADDONLANG->T('autoSetupPayment')}</option>
                        <option value="on">{$ADDONLANG->T('autoSetupOn')}</option>
                        <option value="" selected="" >{$ADDONLANG->T('autoSetupOff')}</option>
                    </select>
                </div>
            </div>
            <input type="submit" name="createSingle" class="btn btn-success" value="{$ADDONLANG->T('saveSingle')}" />
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">{$ADDONLANG->T('multipleProductCreator')}</div>
    <div class="panel-body">
        <form action="" method="post" class="form-horizontal">
            <div class="form-group">
                <label class="control-label col-sm-2">{$ADDONLANG->T('productGroup')}</label>
                <div class="col-sm-10">
                    <select name="gid" class="form-control">
                        {foreach from=$productGroups item=productGroup}
                            <option value="{$productGroup->id}">{$productGroup->name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <input type="submit" name="createMass" class="btn btn-success" value="{$ADDONLANG->T('saveMultiple')}" />
        </form>
    </div>
</div>
