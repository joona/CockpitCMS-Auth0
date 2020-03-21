<?php

$app->on('cockpit.api.authenticate', function($data) use($app) {
    err('Checking user session', var_export($data, true));

    if ($data['token'] && $data['resource'] == 'auth0') {
        $user = $this->module('auth0')->userinfo($data['token'], [
            'normalize' => true,
            'cache'     => $app->retrieve('config/auth0/cache', false)
        ]);

        err('authentication middleware got user', var_export($user, true));

        if ($user) {
            $data['authenticated'] = true;
            $data['user'] = $user;
        }
    }

    err('Authenticate', var_export($data, true));
});

$app->on('cockpit.rest.init', function($routes) use($app) {
  $routes['auth0'] = 'Auth0\\Controller\\RestApi';
});
