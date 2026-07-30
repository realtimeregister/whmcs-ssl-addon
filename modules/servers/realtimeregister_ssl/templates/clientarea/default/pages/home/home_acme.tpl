<script type="text/javascript" src="{$assetsURL}/js/addonLibs.js"></script>

<div class="panel panel-default" style="text-align: left">
    <div class="panel-heading">
        <h2>{$productName}</h2>
    </div>
    <div class="panel-body">
        <table class="table table-bordered">
            <colgroup>
                <col style="width: 25%"/>
                <col style="width: 75%"/>
            </colgroup>
            <tbody>
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('configurationStatus')}</strong></td>
                    <td class="text-left">{$ADDONLANG->T($configurationStatus)}</td>
                </tr>
                {if $validTill}
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('validTill')}</strong></td>
                    <td class="text-left">{$validTill}</td>
                </tr>
                {/if}
                {if $nextInvoiceDate}
                <tr>
                    <td class="text-left"><strong>{$ADDONLANG->T('nextInvoiceDate')}</strong></td>
                    <td class="text-left">{$nextInvoiceDate}</td>
                </tr>
                {/if}
                <tr>
                    <td class="text-left" style="vertical-align: top;"><strong>{$ADDONLANG->T('acmeDomainsLabel')}</strong></td>
                    <td class="text-left">
                        <table class="table table-condensed" style="margin-bottom: 0;">
                            <thead>
                                <tr>
                                    <th>{$ADDONLANG->T('domain')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $domains as $domain}
                                <tr>
                                    <td>{$domain}</td>
                                </tr>
                                {/foreach}
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
