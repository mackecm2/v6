{if isset($BLOCKS)}
<div class="checkout-progress">
	 {foreach from=$BLOCKS item=block} <span class="{$block.class}">
	<!--{$block.step}-->
	{$block.title}</span>
	{/foreach} &nbsp;
</div>
{/if}