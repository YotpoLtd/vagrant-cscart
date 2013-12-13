{strip}
    <div class="top-panel">
        <div id="minimize_block" class="top-panel__wrapper">
            <div class="top-panel__logo">
                <i class="top-panel__icon-basket icon-basket"></i>
            </div>
            <h4 class="top-panel__title">
                {$title nofilter}
            </h4>
            <div class="top-panel-action">
                <span class="top-panel-action_item">&nbsp;</span>
                <a href="{$href|fn_url}" class="top-panel__close top-panel-action_item"><i class="icon-cancel"></i></a>
            </div>
        </div>
        <a id="sw_minimize_block" class="minimize-label cm-combination">
            <i id="on_minimize_block" class="minimize-label__icon icon-down-open hidden"></i><i id="off_minimize_block" class="minimize-label__icon icon-up-open"></i>
        </a>
    </div>
{/strip}