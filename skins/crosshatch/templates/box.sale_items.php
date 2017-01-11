{if $PRODUCTS}
<div class="box">
	<h3>{$LANG.catalogue.title_saleitems}</h3>
	<ol>
		 {foreach from=$PRODUCTS item=product}
		<p class="image">
		<a href="{$product.url}" title="{$product.name}">
		<img src="{$product.image_url}"/>
		</a>
		</p>
		<li>
		<a href="{$product.url}" title="{$product.name}">{$product.name}</a><br/>
		{if {$product.saving}}<span class="saving">{$LANG.catalogue.saving} {$product.saving}</span>{/if} </li>
		 {/foreach}
	</ol>
</div>
{/if}