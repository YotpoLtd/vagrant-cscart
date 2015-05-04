<div class="login-info">
{if $runtime.controller == "auth" && $runtime.mode == "login_form"}
    {__("text_login_form")}
    <a href="{"profiles.add"|fn_url}">{__("register_new_account")}</a>
{elseif $runtime.controller == "auth" && $runtime.mode == "recover_password"}
    <h4>{__("text_recover_password_title")}</h4>
    {__("text_recover_password")}
{/if}
</div>