<div>
    <ul class="uk-breadcrumb">
        @hasaccess?('cockpit', 'accounts')
        <li><a href="@route('/settings')">@lang('Settings')</a></li>
        <li><a href="@route('/accounts')">@lang('Accounts')</a></li>
        @endif
        <li class="uk-active"><span>@lang('Account')</span></li>
    </ul>
</div>

<div class="uk-margin-top uk-viewport-height-1-3 uk-flex uk-flex-center uk-flex-middle">
    <div class="uk-text-center">
        <img src="@base('auth0:assets/auth0.svg')" width="100" alt="Auth0">
        <div class="uk-margin">
            <strong class="uk-h2">Managed By Auth0</strong>
        </div>
    </div>
</div>
