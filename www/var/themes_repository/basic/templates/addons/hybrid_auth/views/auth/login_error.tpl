<script type="text/javascript">
	{if $redirect_url}
		opener.location.href = '{$redirect_url}';
	{else}
		opener.location.reload();
	{/if}
	
	close();
</script>