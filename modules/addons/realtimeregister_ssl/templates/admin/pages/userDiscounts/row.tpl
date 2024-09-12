<tr>
    <td><a target="_blank" href="clientssummary.php?userid={$client.id}">
            #{$client.id} {$client.name}
        </a>
    </td>
    <td><a target="_blank" href="configproducts.php?action=edit&id={$product.id}">
            #{$product.id} {$product.name}
        </a>
    </td>
    <td>{$discount}</td>
    <td>
        {assign var="atLeastOnePrice" value="0"}
        {foreach from=$pricings item=pricing}
            {if $pricing->monthly !== '-1.00'}
                {assign var="atLeastOnePrice" value="1"}
                <span><strong>{$pricing->code}</strong></span>
                <p style="margin-bottom: 0 !important;">{$ADDONLANG->T('table', 'basePrice')}{$pricing->monthly}</p>
                <p>{$ADDONLANG->T('table', 'priceWithDiscount')}{$pricing->discountMonthly}</p>
            {/if}           
        {/foreach}
        {if $atLeastOnePrice == "0"}
            <span>-</span>
        {/if}
    </td>
    <td>
        {assign var="atLeastOnePrice" value="0"}
        {foreach from=$pricings item=pricing}
            {if $pricing->annually !== '-1.00'}
                {assign var="atLeastOnePrice" value="1"}
                <span><strong>{$pricing->code}</strong></span>
                <p style="margin-bottom: 0 !important;">{$ADDONLANG->T('table', 'basePrice')}{$pricing->annually}</p>
                <p>{$ADDONLANG->T('table', 'priceWithDiscount')}{$pricing->discountAnnually}</p>
            {/if} 
        {/foreach}
        {if $atLeastOnePrice == "0"}
            <span>-</span>
        {/if}
    </td>
    <td>
        {assign var="atLeastOnePrice" value="0"}
        {foreach from=$pricings item=pricing}
            {if $pricing->biennially !== '-1.00'}
                {assign var="atLeastOnePrice" value="1"}
                <span><strong>{$pricing->code}</strong></span>
                <p style="margin-bottom: 0 !important;">{$ADDONLANG->T('table', 'basePrice')}{$pricing->biennially}</p>
                <p>{$ADDONLANG->T('table', 'priceWithDiscount')}{$pricing->discountBiennially}</p>
            {/if}  
        {/foreach}
        {if $atLeastOnePrice == "0"}
            <span>-</span>
        {/if}
    </td>
    <td>
        {assign var="atLeastOnePrice" value="0"}
        {foreach from=$pricings item=pricing}
            {if $pricing->triennially !== '-1.00'}
                {assign var="atLeastOnePrice" value="1"}
                <span><strong>{$pricing->code}</strong></span>
                <p style="margin-bottom: 0 !important;">{$ADDONLANG->T('table', 'basePrice')}{$pricing->triennially}</p>
                <p>{$ADDONLANG->T('table', 'priceWithDiscount')}{$pricing->discountTriennially}</p>
            {/if} 
        {/foreach}
        {if $atLeastOnePrice == "0"}
            <span>-</span>
        {/if}
    </td> 
    <td>   
    <td>
        <div align="center">
            <button data-toggle="tooltip" title="{$ADDONLANG->T('editItem')}" type="button"  data-id="{$rule_id}" class="btn btn-primary btn-inverse editItem"><i class="fa fa-pencil" aria-hidden="true"></i></button>
            <button data-toggle="tooltip" title="{$ADDONLANG->T('deleteItem')}" type="button" data-id="{$rule_id}" class="btn btn-danger  btn-inverse deleteItem"><i class="fa fa-trash" aria-hidden="true"></i></button>
        </div>
    </td>
</tr>
