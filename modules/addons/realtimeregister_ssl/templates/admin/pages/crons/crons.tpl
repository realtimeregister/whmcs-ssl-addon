{if $formError}
    <div class="col-lg-12">
        <div class="note note-danger">
            <button type="button" class="close" data-dismiss="alert"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>
            <p><strong>{$formError}</strong></p>
        </div>
    </div>
{/if}
<div class="panel panel-default">
    <div class="panel-heading">{$ADDONLANG->T('header')}</div>
    <div class="panel-body">{$ADDONLANG->T('explanation')}</div>
</div>
<div class="panel panel-default">
    <div class="panel-heading">{$ADDONLANG->T('header')}</div>
    <div class="panel-body">
        {$form}
    </div>
</div>