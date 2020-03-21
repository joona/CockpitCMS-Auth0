<?php

$app->on('app.render.view', function(&$view) use($app) {
  if($view == 'cockpit:views/_partials/logincheck.php') {
    err("Overriding logincheck");
    $view = 'auth0:cockpit/views/_partials/logincheck.php';
  }
});

$app->bind('/accounts/account', function() use($app) {
  $user = $this->module('cockpit')->getUser();

  if (!$user) {
    $this->reroute('/auth/login');
    $this->stop();
  }

  $app["user"] = $user;

  return $this->view('auth0:cockpit/views/accounts/account.php with cockpit:views/layouts/app.php');
});
