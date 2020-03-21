<?php

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

function err() {
  if(!getenv('AUTH0_DEBUG')) return;
  $args = func_get_args();
  $str = join(' ', $args);
  error_log($str);
}

$this->module('auth0')->extend([
  'getOrCreateUser' => function($info) use($app) {
    if(!isset($info['sub'])) return null;
    if(!isset($info['email'])) return null;

    $maybeUser = $this->app->storage->findOne('cockpit/accounts', ['user' => $info['sub']]);
    err("Got cockpit user:", var_export($maybeUser, true));

    if(!$maybeUser) {
      err("User not found with id", var_export($maybeUser, true));

      $now = time();
      $emailParts = explode('@', $info['email']);
      $username = $emailParts[0];

      $user = [
        '_modified' => $now,
        '_created' => $now,
        'user'   => $info['sub'],
        'name' => $info['name'],
        'email'  => 'auth0:'.$info['email'],
        'active' => true,
        'group'  => 'auth0user',
        'i18n'   => $app->helper('i18n')->locale,
        'auth0'  => $info['sub'],
        'generated' => true
      ];

      if(isset($info['locale'])) {
        //$user['i18n'] = $info['locale'];
      }

      $this->app->storage->insert('cockpit/accounts', $user);
      err("Auth0 user added:", var_export($user, true));
      $maybeUser = $user;
      $maybeUser['_fresh'] = true;
    }

    return $maybeUser;
  },

  'userinfo' => function($token, $options = []) use($app) {
    $options = array_merge([
      'normalize' => false,
      'cache' => false
    ], $options);

    $domain = $app->retrieve('config/auth0/domain', false);
    $namespace = $app->retrieve('config/auth0/namespace', 'https://'.$domain);

    $info = $this->app->helper('cache')->read("auth0.user.{$domain}.{$token}", null);

    if (!$info) {
      $ch = curl_init('https://'.$domain.'/userinfo');

      //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer {$token}",
        "Content-type: application/json"
      ]);

      curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$token}"]);

      $result = curl_exec($ch);
      err("Auth0 response", var_export($result, true));

      if(!$result) {
        trigger_error(curl_error($ch));
        return null;
      }
      curl_close($ch);

      $info = json_decode($result, true);
    } else {
      err("Got cached info", var_export($result, true));
    }

    if($info['error']) {
      err("Auth0 Error", var_export($info, true));
      $this->app->helper('cache')->write("auth0.user.{$domain}.{$token}", null, $options['cache']);
      return null;
    }

    if ($info && $options['cache']) {
      $this->app->helper('cache')->write("auth0.user.{$domain}.{$token}", $info, $options['cache']);
    }

    if ($info && $options['normalize']) {
      $userGroup = $app->retrieve('config/auth0/default_group', 'auth0user');

      if(isset($info[$namespace.'/cockpit'])) {
        $userGroup = $info[$namespace.'/cockpit'];
      }

      $maybeRoles = $info[$namespace.'/roles'];
      if(isset($maybeRoles) && is_array($maybeRoles)) {
        $isAdmin = in_array('admin', $maybeRoles)
          || in_array('cockpit:admin', $maybeRoles);
        if($isAdmin) {
          $userGroup = 'admin';
        }
      }

      // ger or create cockpit account for user
      $cockpitUser = $app->module('auth0')->getOrCreateUser($info);

      $user = [
        '_id'   => $cockpitUser['_id'],
        'name'  => $info['name'],
        'email' => $info['email'],
        'group' => $userGroup
      ];

      $user['auth0'] = $info;
      $user['cockpit_user'] = $cockpitUser;
      $info = $user;
    }


    $info['auth0token'] = $token;

    err("Userinfo", var_export($info, true));
    return $info;
  }
]);

if (!$app->retrieve('config/auth0/enabled')) {
  return;
}

$app('acl')->addResource('cockpit', [
  'backend', 'finder', 'accounts', 'settings', 'rest', 'webhooks', 'info'
]);

// override views
$app->path('cockpit', __DIR__.'/cockpit');

$app->on('cockpit.bootstrap', function() use($app) {
  $app('session')->init();
});

// REST
if (COCKPIT_API_REQUEST) {
  // INIT REST API HANDLER
  include_once(__DIR__.'/api.php');
}

// ADMIN
if (COCKPIT_ADMIN && !COCKPIT_API_REQUEST) {

  include_once(__DIR__.'/admin.php');
}
