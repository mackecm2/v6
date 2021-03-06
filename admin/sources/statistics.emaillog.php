{*
 * CubeCart v6
 * ========================================
 * CubeCart is a registered trade mark of CubeCart Limited
 * Copyright CubeCart Limited 2015. All rights reserved.
 * UK Private Limited Company No. 5323904
 * ========================================
 * Web:   http://www.cubecart.com
 * Email:  sales@cubecart.com
 * License:  GPL-3.0 https://www.gnu.org/licenses/quick-guide-gplv3.html
 *}
<div id="email_log" class="tab_content">
  <h3>{$LANG.settings.title_email_log}</h3>
  <table>
	<thead>
	  <tr>
	  	<td>{$LANG.common.sent}</td>
		<td>{$LANG.common.date}</td>
		<td>{$LANG.common.subject}</td>
		<td>{$LANG.common.to}</td>
		<td>{$LANG.common.from}</td>
		<td>&nbsp;</td>		
	  </tr>
	</thead>
	<tbody>
	{foreach from=$EMAIL_LOG item=log}
	  <tr>
	  	<td align="center">{if $log.result==1}<i class="fa fa-check" title="{$LANG.common.yes}"></i>{else}<i class="fa fa-times" title="{$LANG.common.no}"></i>{/if}</td>
		<td>{$log.date}</td>
		<td>
			<a href="#" onclick="{literal}$.colorbox({title:'{/literal}{$log.subject} (HTML){literal}',width:'90%', height:'90%', html:'<iframe width=\'100%\' height=\'95%\' frameBorder=\'0\' src=\'?_g=xml&amp;function=viewEmail&amp;id={/literal}{$log.id}{literal}&amp;mode=content_html\'></iframe>'}){/literal}">{$log.subject}</a>
		</td>
		<td>{$log.to}</td>
		<td>{$log.from}</td>
		<td>{if $log.email_content_id>0}<a href="?_g=documents&amp;node=email&amp;type=content&amp;action=edit&amp;content_id={$log.email_content_id}"><i class="fa fa-pencil-square-o" title="{$LANG.common.edit}"></i></a>{/if}</td>
	  </tr>
	{foreachelse}
	  <tr>
		<td colspan="4" align="center" width="650"><strong>{$LANG.form.none}</strong></td>
	  </tr>
	{/foreach}
	</tbody>
  </table>
  <div>{$PAGINATION_EMAIL_LOG}</div>
</div>