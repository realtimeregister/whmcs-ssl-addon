<script type="text/javascript" src="{$assetsURL}/js/addonLibs.js"></script>

<table id="mainTable" class="table table-bordered">
    <colgroup>
        <col style="width: 25%"/>
        <col style="width: 75%"/>
    </colgroup>
    <tbody>
        <tr>
            <td class="text-left">{$ADDONLANG->T('productName')}</td>
            <td class="text-left">{$productName|escape}</td>
        </tr>
        <tr>
            <td class="text-left">{$ADDONLANG->T('configurationStatus')}</td>
            <td class="text-left">{$ADDONLANG->T('awaitingConfiguration')}
                <a class="btn btn-default" href="{$configurationURL|escape:'htmlall'}">{$ADDONLANG->T('configureNow')}</a></td>
        </tr>
    </tbody>
</table>
