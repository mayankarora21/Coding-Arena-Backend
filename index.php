<?php

header("Access-Control-Allow-Origin: *");
require 'vendor/autoload.php';

$config = array(
    "client_id" => "6491fefabc896cf8de5a5cb8297802de",
    "client_secret" => "c7c47d03ffc7969ccd9ed3bb39ce1581",
    'api_endpoint'=> 'https://api.codechef.com/',
    'access_token_endpoint'=> 'https://api.codechef.com/oauth/token',
    'redirect_uri'=> 'https://codingarena21.herokuapp.com'
);

function generate_access_token($oauth_details){
    global $config;
    $oauth_config = array(
        'grant_type' => 'authorization_code',
        'code'=> $oauth_details['authorization_code'],
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret'],
        'redirect_uri'=> $config['redirect_uri']
    );
    $response = json_decode(make_curl_request($config['access_token_endpoint'], $oauth_config), true);
    $result = $response['result']['data'];

    $oauth_details['access_token'] = $result['access_token'];
    $oauth_details['refresh_token'] = $result['refresh_token'];
    $oauth_details['scope'] = $result['scope'];

    return $oauth_details;
}

function generate_access_token_from_refresh_token($config, $oauth_details){
    $oauth_config = array(
        'grant_type' => 'refresh_token',
        'refresh_token'=> $oauth_details['refresh_token'],
        'client_id' => $config['client_id'],
        'client_secret' => $config['client_secret']
    );
    $response = json_decode(make_curl_request($config['access_token_endpoint'], $oauth_config), true);
    $result = $response['result']['data'];

    $oauth_details['access_token'] = $result['access_token'];
    $oauth_details['refresh_token'] = $result['refresh_token'];
    $oauth_details['scope'] = $result['scope'];

    return $oauth_details;

}

function make_curl_request($url, $post = FALSE, $headers = array()){
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
    }

    $headers[] = 'content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    return $response;
}

$app = new \Slim\App;

$app->get('/', function ($request, $response) {
    
    // Parse authorization token
    $parsedBody = $request->getQueryParams();
    $authorization_code = '';
    if(array_key_exists('code', $parsedBody))
        $authorization_code = $parsedBody['code'];
    else
        return $response->write('Please provide an authorization code');
    // OAuth details
    // Note: Authorization code expires in 30 seconds.
    $oauth_details = array(
        'authorization_code' => $authorization_code,
        'access_token' => '',
        'refresh_token' => ''
    );
    
    // Get access token
    $oauth_details = generate_access_token($oauth_details);
    
    //$oauth_details = generate_access_token_from_refresh_token($config, $oauth_details);         //use this if you want to generate access_token from refresh_token
    
    // Return OAuth details in response
    return $response->write(json_encode($oauth_details));
});

// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});

$app->run();

?>