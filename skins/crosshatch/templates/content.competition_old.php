<h2>Competition</h2>
<p>
	<table align="center" border="0" cellpadding="1" cellspacing="1">
	<tbody>
		<tr>
			<td><h3>VISIONS OF RAINBOW - Your chance to win one of 3 copies!</h3></td>
		</tr>
		<tr>
			<td><a href="https://www.wymeruk.co.uk/visions-of-rainbow.html" target="_blank"><img alt="Click here for more details (opens in a new window)" src="https://www.wymeruk.co.uk/images/cache/51mSnbTuf3L._AA160_.270.jpg" /></a></td>
		</tr>
		<tr>
			<td>Enter your name and email address below, and the magic word RAINBOW to enter.<br />
			&nbsp;<br />
			Winners will be notified by email only after which point we will ask for your shipping address.<br />
			&nbsp;<br />
			Competition will run for 14 days from 7th - 21st May 
and will close at midnight GMT.<br />
			&nbsp;<br />
			Winners will be notified by 31st May and the book will be sent out upon publication in June.<br />
			&nbsp;<br />
			(Please note: we do not supply customer details to third parties)</td>
		</tr>
	</tbody>
</table>

<p>&nbsp;</p>
</p>
<form action="{$VAL_SELF}" method="post" name="competition">
	<fieldset>
		 {foreach from=$LOGIN_HTML item=html} {$html} {/foreach}
		<div>
			<label for="competition-firstname">{$LANG.user.name_first}</label><span><input type="text" name="first_name" id="competition-firstname" value="{$DATA.first_name}" class="required"/> *</span>
		</div>
		<div>
			<label for="competition-lastname">{$LANG.user.name_last}</label><span><input type="text" name="last_name" id="competition-lastname" value="{$DATA.last_name}" class="required"/> *</span>
		</div>
		<div>
			<label for="competition-email">{$LANG.common.email}</label><span><input type="text" name="email" id="competition-email" value="{$DATA.email}" class="required"/> *</span>
		</div>
		<div>
			<label for="competition-code">Enter the Competition Code here</label><span><input type="text" name="code" id="competition-code" class="textbox required" value="{$DATA.code}"/> *</span>
		</div>
		<div>
			<label for="competition-terms">&nbsp;</label><span><input type="checkbox" id="competition-terms" name="terms_agree" value="1" checked="checked" style="display:none" {$terms_conditions_checked}/><a href="{$TERMS_CONDITIONS}" target="_blank"></a></span>
		</div>
		<div>
			<label for="competition-mailing">&nbsp;</label><span><input type="checkbox" id="competition-mailing" name="mailing_list" value="1" {if !isset($data.mailing_list) || $data.mailing_list == 1}checked="checked"{/if}/>{$LANG.account.register_mailing}/I am already a subscriber</a></span>
		</div>
	</fieldset>
	<div>
		<input type="submit" name="competition" value="Enter Now!" class="button_submit"/>
	</div>
</form>