{capture name="hybrid_auth"}
{strip}
	{foreach $addons.hybrid_auth key="option_name" item="value"}
		{if $option_name|strpos:"_status" !== false && $value == "Y"}
			{$provider_id = "_status"|str_replace:"":$option_name}

			<a class="cm-login-provider" data-idp="{$provider_id}"><img src="{$images_dir}/addons/hybrid_auth/images/{$provider_id}.png" title="{$provider_id}" /></a>
		{/if}
	{/foreach}
{/strip}
{/capture}

{if $smarty.capture.hybrid_auth}
	{__("hybrid_auth_social_login")}:

	<p class="text-center">{$smarty.capture.hybrid_auth nofilter}</p>
{/if}