{if $enableLabel}
    <label for="{$formName}_{$name}" class="col-sm-3 control-label">{$ADDONLANG->T('label')}</label>
{/if}
<div class="col-sm-{$colWidth}">
  <input name="{$nameAttr}" type="text" value="{$value}"  class="form-control" {if $addIDs}id="{$addIDs}_{$name}"{/if} placeholder="{if $enablePlaceholder}{$ADDONLANG->T('placeholder')}{/if}" {foreach from=$dataAttr key=dataKey item=dataValue}data-{$dataKey}="{$dataValue}"{/foreach}>
  {if $enableDescription}
    <span class="help-block">{$ADDONLANG->T('description')}</span>
  {/if}
    <span class="help-block error-block"{if !$error}style="display:none;"{/if}>{$error}</span>
</div>