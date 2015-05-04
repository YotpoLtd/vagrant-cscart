<div class="control-group">
    <label class="control-label" for="rapid_merchant_id">{__("merchant_id")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][merchant_id]" id="rapid_merchant_id" value="{$processor_params.merchant_id}" class="input-text-large"  size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="rapid_username">{__("username")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][username]" id="rapid_username" value="{$processor_params.username}" class="input-text-large"  size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="rapid_password">{__("password")}:</label>
    <div class="controls">
        <input type="text" name="payment_data[processor_params][password]" id="rapid_password" value="{$processor_params.password}" class="input-text-large"  size="60" />
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="rapid_mode">{__("test_live_mode")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][mode]" id="rapid_mode">
            <option value="test" {if $processor_params.mode == "test"}selected="selected"{/if}>{__("test")}</option>
            <option value="live" {if $processor_params.mode == "live"}selected="selected"{/if}>{__("live")}</option>
        </select>
    </div>
</div>
