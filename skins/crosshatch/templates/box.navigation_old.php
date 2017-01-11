<div id="nav">
	<div class="navContainer">
		<ul class="dropdown">
			<li><a href="{$STORE_URL}/index.php" title="{$LANG.navigation.homepage}">{$LANG.navigation.homepage}</a></li>
			 {$NAVIGATION_TREE} {if $CTRL_CERTIFICATES && !$CATALOGUE_MODE}
			<li class="li-nav"><a href="{$URL.certificates}" title="{$LANG.navigation.giftcerts}">{$LANG.navigation.giftcerts}</a></li>
			 {/if} {if $CTRL_SALE}
			<li class="li-nav"><a href="{$URL.saleitems}" title="{$LANG.navigation.saleitems}">{$LANG.navigation.saleitems}</a></li>
			 {/if}
		</ul>
	</div>
	<div class="navContainer">
		<div id="search">
		<form id="searchForm" action="{$ROOT_PATH}index.php?_a=category" method="get">
			<fieldset>
				<div class="input">
					<input type="text" name="search[keywords]" id="s" value="{$LANG.search.input_default}"/>
					<input type="hidden" name="_a" value="category"/>
				</div>
				<input id="searchSubmit" type="submit" value="{$LANG.common.search}"/>
			</fieldset>
		</form>
		<a href="{$ROOT_PATH}index.php?_a=search" class="searchAdvanced">{$LANG.search.advanced}</a>
		</div>
	</div>
</div>
<div class="clear">
	&nbsp;
</div>