<input type="hidden" id="sslOrderType" value="{$orderType}" />
<h4>{$pageTitle}</h4>
<div class="box light">
    <div class="row">
        <div class="col-lg-12" id="addon-categories-content" >
            <table class="table table-striped dataTable no-footer" id="addon-data-list" >
                <thead>
                    <tr>
                        <th>{$ADDONLANG->T('sslSummaryOrdersPage','Product/Service')}</th>
                        <th>{$ADDONLANG->T('sslSummaryOrdersPage','Pricing')}</th>
                        <th>{$ADDONLANG->T('sslSummaryOrdersPage','Next Due Date')}</th>
                        <th>{$ADDONLANG->T('sslSummaryOrdersPage','Status')}</th>
                    </tr>
                </thead>
                <tbody>
                </tbody> 
            </table>
        </div>
    </div>
</div>
{literal}
    <script type="text/javascript">        
        var zeroRecordsLang = '{/literal}{$ADDONLANG->absoluteT('Nothing to display')}{literal}';
        var searchLang = '{/literal}{$ADDONLANG->absoluteT('Search')}{literal}';
        var previousLang = '{/literal}{$ADDONLANG->absoluteT('Previous')}{literal}';
        var nextLang = '{/literal}{$ADDONLANG->absoluteT('Next')}{literal}';
    </script>
    <script type="text/javascript" src="{/literal}{$assetsURL}{literal}/js/pages/orders.js"></script>

{/literal}
<link rel="stylesheet" type="text/css" href="{$assetsURL}/css/pages/orders.css" /> 
