<div>
	<h2>{$LANG.account.your_account}</h2>
	<div id="myaccount">
		<ul>
			<li><a href="{$STORE_URL}/index.php?_a=profile" class="button_submit" title="{$LANG.account.your_details}">{$LANG.account.your_details}</a></li><br />
			<li><a href="{$STORE_URL}/index.php?_a=addressbook" class="button_submit" title="{$LANG.account.your_addressbook}">{$LANG.account.your_addressbook}</a></li><br />
			<li><a href="{$STORE_URL}/index.php?_a=vieworder" class="button_submit" title="{$LANG.account.your_orders}">{$LANG.account.your_orders}</a></li><br />
			<li><a href="{$STORE_URL}/index.php?_a=downloads" class="button_submit" title="{$LANG.account.your_downloads}">{$LANG.account.your_downloads}</a></li><br />
			<li><a href="{$STORE_URL}/index.php?_a=newsletter" class="button_submit" title="{$LANG.account.your_subscription}">{$LANG.account.your_subscription}</a></li><br />
			 {foreach from=$ACCOUNT_LIST_HOOKS item=list_item}
			<li><a href="{$list_item.href}" title="{$list_item.title}">{$list_item.title}</a></li><br />
			 {/foreach}
		</ul>
	</div>
</div>