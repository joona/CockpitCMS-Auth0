<?php

namespace Auth0\Controller;

class RestApi extends \LimeExtra\Controller {
  public function authorize() {
    $token = $this->param('token', null);

    if (!$token) {
      return [
        'authorized' => false,
        'reason' => 'empty_token'
      ];
    }

    $user = $this->module('auth0')->userinfo($token, ['normalize'=>true]);

    if (!$user) {
      return [
        'authorized' => false,
        'reason' => 'invalid_token'
      ];
    }

    if ($this->module("cockpit")->hasaccess('cockpit', 'backend', @$user['group'])) {
      // check session ttl from config
      $sessionTTL = $this->app->retrieve('config/auth0/session_ttl', 3600);
      \session_start([
        'cookie_lifetime' => $sessionTTL,
      ]);

      // set user to session
      $this->module('cockpit')->setUser($user, true);

      err('_SESSION after authorization', var_export($_SESSION['cockpit.app.auth'], true));

      return array_merge([
        'authorized' => true
      ], $user);
    }

    return [
      'authorized' => false,
      'reason' => 'no_access'
    ];
  }

}
