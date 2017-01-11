<div class="box">
	<h3>{$LANG.catalogue.title_popular}</h3>
	<ol>
		 {foreach from=$POPULAR item=product}
		 		<p class="image">
			<a href="{$product.url}" title="{$product.name}">
			<img src="{$product.image_url}"/>
			</a>
		</p>
		<li><a href="{$product.url}" title="{$product.name}">{$product.name}</a></li>
		 {/foreach}
	</ol>
</div>