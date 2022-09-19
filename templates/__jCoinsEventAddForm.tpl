{if MODULE_JCOINS && $__wcf->session->getPermission('user.jcoins.calendar.canAddFee')}
	<dl>
		<dt><label for="jCoinsFee">{lang}wcf.jcoins.calendar.fee.jcoins{/lang}</label></dt>
		<dd>
			<input type="number" id="jCoinsFee" name="jCoinsFee" class="short" value="{@$jCoinsFee}" min="0">
			<small>{lang}wcf.jcoins.calendar.fee.jcoins.description{/lang}</small>
		</dd>
	</dl>
	
	<dl>
		<dt><label for="jCoinsReFee">{lang}wcf.jcoins.calendar.reFee.jcoins{/lang}</label></dt>
		<dd>
			<input type="number" id="jCoinsReFee" name="jCoinsReFee" class="short" value="{@$jCoinsReFee}" min="0">
			<small>{lang}wcf.jcoins.calendar.reFee.jcoins.description{/lang}</small>
		</dd>
	</dl>
{/if}
