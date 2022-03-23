<?php

namespace Example;

use Services\SignatureClientService;
use Example\Services\Examples\eSignature\SigningViaEmailService;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Client\ApiException;


require "vendor/autoload.php";
require "ds_config_jwt_mini.php";

$rsaPrivateKey = file_get_contents($GLOBALS['JWT_CONFIG']['private_key_file']);
$integration_key = $GLOBALS['JWT_CONFIG']['ds_client_id'];
$impersonatedUserId = $GLOBALS['JWT_CONFIG']['ds_impersonated_user_id'];
$scopes = "signature impersonation";



$config = new Configuration();
$apiClient = new ApiClient($config);
$args = [];
// Collect user information through prompts
echo "Welcome to the JWT Code example!\n";
echo "Enter the signer's email address: \n";
$args['envelope_args']['signer_email'] = trim(fgets(STDIN));

echo "Enter the signer's name: \n";
$args['envelope_args']['signer_name'] = trim(fgets(STDIN));

echo "Enter the carbon copy's email address: \n";
$args['envelope_args']['cc_email'] = trim(fgets(STDIN));

echo "Enter the carbon copy's name: \n";
$args['envelope_args']['cc_name'] = trim(fgets(STDIN)); 


$clientService = new SignatureClientService($args);
// get the information from the app.config file




try {
    $apiClient->getOAuth()->setOAuthBasePath("account-d.docusign.com");
    $response = $apiClient->requestJWTUserToken($integration_key, $impersonatedUserId, $rsaPrivateKey, $scopes, 60);



} catch (\Throwable $th) {
    var_dump($th);
    // we found consent_required in the response body meaning first time consent is needed
    if (strpos($th->getMessage(), "consent_required") !== false) {
        $authorizationURL = 'https://account-d.docusign.com/oauth/auth?' . http_build_query([
            'scope'         => $scopes,
            'redirect_uri'  => 'https://httpbin.org/get',
            'client_id'     => $integration_key,
            'response_type' => 'code'
        ]);

        echo "It appears that you are using this integration key for the first time.  Please visit the following link to grant consent authorization.\n\n";
        echo $authorizationURL;


        exit();
    }
}

// We've gotten a JWT token, now we can use it to make API calls
if (isset($response)) {
    $access_token = $response[0]['access_token'];
    // retrieve our API account Id
    
    $info = $apiClient->getUserInfo($access_token);
    $account_id = $info[0]["accounts"][0]["account_id"];

    // Instantiate the API client again with the default header set to the access token
    $config->setHost("https://demo.docusign.net/restapi");
    $config->addDefaultHeader('Authorization', 'Bearer ' . $access_token);
    $apiClient = new ApiClient($config);

    try {
        
        $envelopeResponse = SigningViaEmailService::signingViaEmail($args, $clientService, $demoDocsPath);
        echo "Envelope ID: " . $envelopeResponse[0]->getEnvelopeId() . "\n";

    } catch (ApiException $e) {
        var_dump($e);
        exit;
    }
}
