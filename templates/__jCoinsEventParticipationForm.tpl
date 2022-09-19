{if MODULE_JCOINS}
	{if $event->jCoinsFee || $event->jCoinsReFee}
		<dl>
			<dt>{lang}wcf.jcoins.calendar.fee.form{/lang}</dt>
			<dd>{lang}wcf.jcoins.calendar.fee.form.amount{/lang}</dd>
		</dl>
	{/if}
{/if}
