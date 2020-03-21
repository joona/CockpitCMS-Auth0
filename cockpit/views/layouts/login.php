<!doctype html>
<html class="uk-height-1-1" lang="en" data-base="@base('/')" data-route="@route('/')">
<head>
    <meta charset="UTF-8">
    <title>@lang('Authenticate Please!')</title>
    <link rel="icon" href="@base('/favicon.ico')" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

    {{ $app->assets($app['app.assets.base'], $app['cockpit/version']) }}
    {{ $app->assets(['assets:lib/uikit/js/components/form-password.min.js'], $app['cockpit/version']) }}

    <style>
      #login-container {
        border-radius: 10px;
      }
      <?= $app->retrieve('config/auth0/styles', '') ?>
    </style>

    <script type="text/javascript" src="https://cdn.auth0.com/js/lock/11.22.3/lock.min.js"></script>


</head>
<body class="login-page uk-height-viewport uk-flex uk-flex-middle uk-flex-center">
    <div>
        <div class="uk-display-block uk-panel-box uk-panel-card uk-panel-card-hover" id="login-container" style="padding:0;"></div>
        <div class="uk-text-center uk-margin">
            <button id="logout" class="uk-button uk-button-large uk-button-outline uk-text-danger uk-hidden" type="button" name="button">Logout</button>
        </div>
    </div>


<script type="text/javascript">
const AUTH0_ID = '<?= $app['config/auth0/secret'] ?>';
const AUTH0_DOMAIN = '<?= $app['config/auth0/domain'] ?>';
const AUTH0_LOCK_OPTIONS = <?= $app->retrieve('config/auth0/lock_options', '{}') ?>;

const isLogoutAction = location.search.indexOf('logout=1') > -1;
const logoutButton = document.getElementById('logout');

const lock = new Auth0Lock('<?= $app['config/auth0/id'] ?>', '<?= $app['config/auth0/domain'] ?>', Object.assign({
  container: 'login-container',
  allowSignUp: false,
  auth: {
    sso: false,
    params: {
      scopes: '<?= $app['config/auth0/scope'] ?>'
    }
  }
}, AUTH0_LOCK_OPTIONS || {}));

function logout(isAction) {
  logoutButton.classList.add('uk-hidden');
  localStorage.removeItem('cockpit.auth0.accessToken');

  if(isAction) {
    lock.logout({
      returnTo: '<?= $app->getSiteUrl(true) ?>/auth/login'
    });
  }
}

function loggedIn(user, isFresh) {
  logoutButton.classList.remove('uk-hidden');
  console.log('user authorized, redirecting...');

  setTimeout(() => {
    App.reroute('/');
  }, 500);
}

function authorize(token, isFresh) {
  if(!token) return;

  const data = { token: token, auth0: true };

  fetch('/api/auth0/authorize', 
    {
      method: 'POST',
      mode: 'cors',
      cache: 'no-cache',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(response => {
      if(!response) {
        alert('invalid response from server');
        return;
      }

      if(!response.authorized) {
        console.warn('authorization failed:', response);
        logout();
      } else if(response.authorized == true) {
        console.log('user authorized:', response);
        loggedIn(response, isFresh);
      }
    })
    .catch(err => {
      console.error('Authorization failure:', err.message);
      console.error(err.stack);
      logout();
    });
}

function reviveSession() {
  const maybeToken = window.localStorage.getItem('cockpit.auth0.accessToken');

  if(maybeToken) {
    if(isLogoutAction) {
      logout();
      return;
    }

    authorize(maybeToken);
  }
}

reviveSession();
lock.show();

lock.on('authenticated', auth => {
  // validate the token first
  lock.getUserInfo(auth.accessToken, (err, profile) => {
    if(err) {
      console.error('Unexpected error while authenticating:', err);
      console.error(err.stack);
      alert(`Unexpected error: ${err.message}`);
      return;
    }

    window.localStorage.setItem('cockpit.auth0.accessToken', auth.accessToken);
    authorize(auth.accessToken, true);
  });
});

</script>
</body>
</html>
