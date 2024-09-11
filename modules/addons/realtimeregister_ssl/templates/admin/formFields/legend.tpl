<div class="col-sm-12">
    <legend {if $addIDs}id="{$addIDs}_{$name}"{/if} {foreach from=$dataAttr key=dataKey item=dataValue}data-{$dataKey}="{$dataValue}"{/foreach} >{$ADDONLANG->T('label')}</legend>
</div>
