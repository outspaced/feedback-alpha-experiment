<?php

ini_set('display_errors', true);
session_start();

require_once('vendor/autoload.php');

$fb = new Facebook\Facebook([
  'app_id' => getenv('FB_APP_ID'),
  'app_secret' => getenv('FB_APP_SECRET'),
  'default_graph_version' => 'v2.4',
  //'default_access_token' => '{access-token}', // optional
]);

// Use one of the helper classes to get a Facebook\Authentication\AccessToken entity.
$helper = $fb->getRedirectLoginHelper();
$accessToken = $helper->getAccessToken();
$loginUrl = $helper->getLoginUrl('http://feedback-alpha.outspaced.com/fb.php', ['email', 'public_profile']);

echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>';

try {
  // Get the Facebook\GraphNodes\GraphUser object for the current user.
  // If you provided a 'default_access_token', the '{access-token}' is optional.
  $response = $fb->get('/me', $accessToken);

} catch(Facebook\Exceptions\FacebookResponseException $e) {
  // When Graph returns an error
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  // When validation fails or other local issues
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}



$me = $response->getGraphUser();
echo 'Logged in as ' . $me->getName();
