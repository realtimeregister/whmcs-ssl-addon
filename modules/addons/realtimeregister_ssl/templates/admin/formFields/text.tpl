{if $enableLabel}
    <label {if $id} for="{$id}" {elseif $addIDs}for="{$addIDs}_{$name}"{/if} class="col-sm-{$labelcolWidth} control-label">{$ADDONLANG->T('label')}</label>
{/if}
<div class="col-sm-{$colWidth}">
  <input name="{$nameAttr}" type="text" value="{$value}"  class="form-control" {if $id} id="{$id}" {elseif $addIDs}id="{$addIDs}_{$name}"{/if}
         {if $readonly||$disabled}readonly=""{/if}
         placeholder="{if $enablePlaceholder}{$ADDONLANG->T('placeholder')}{/if}"
         {foreach from=$dataAttr key=dataKey item=dataValue}data-{$dataKey}="{$dataValue}"{/foreach} {if $required}required{/if}>
  <div class="help-block with-errors">{$error}</div>
  {if $enableDescription}
    <span class="help-block">{$ADDONLANG->T('description')}</span>
  {/if}
</div>
