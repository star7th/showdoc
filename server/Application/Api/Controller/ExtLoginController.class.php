<?php
namespace Api\Controller;
use Think\Controller;
class ExtLoginController extends BaseController {


    public function oauth2(){
        $provider = new \League\OAuth2\Client\Provider\GenericProvider([
            'clientId'                => 'a36df4c9-5ed4-440b-8f69-7535d2947213',    // The client ID assigned to you by the provider
            'clientSecret'            => 'F2m6MjIwNTIwMjEyMjE3NDYxMTM8Lr',    // The client password assigned to you by the provider
            'redirectUri'             => 'http://192.168.8.160:8280/showdoc/server/?s=/api/ExtLogin/oauth2',
            'urlAuthorize'            => 'https://192.168.8.160:8443/maxkey/authz/oauth/v20/authorize',
            'urlAccessToken'          => 'https://192.168.8.160:8443/maxkey/authz/oauth/v20/token',
            'urlResourceOwnerDetails' => 'https://192.168.8.160:8443/maxkey/authz/oauth/v20/resource',
        ],[
            'httpClient' => new \GuzzleHttp\Client(['verify' => false]),
        ]);
        
        // If we don't have an authorization code then get one
        if (!isset($_GET['code'])) {
        
            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $provider->getAuthorizationUrl();
        
            // Get the state generated for you and store it to the session.
            $_SESSION['oauth2state'] = $provider->getState();
        
            // Redirect the user to the authorization URL.
            header('Location: ' . $authorizationUrl);
            exit;
        
        // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || (isset($_SESSION['oauth2state']) && $_GET['state'] !== $_SESSION['oauth2state'])) {
        
            if (isset($_SESSION['oauth2state'])) {
                unset($_SESSION['oauth2state']);
            }
        
            exit('Invalid state');
        
        } else {
        
            try {
        
                // Try to get an access token using the authorization code grant.
                $accessToken = $provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);
        
                // We have an access token, which we may use in authenticated
                // requests against the service provider's API.
                echo 'Access Token: ' . $accessToken->getToken() . "<br>";
                echo 'Refresh Token: ' . $accessToken->getRefreshToken() . "<br>";
                echo 'Expired in: ' . $accessToken->getExpires() . "<br>";
                echo 'Already expired? ' . ($accessToken->hasExpired() ? 'expired' : 'not expired') . "<br>";
                
                $res = http_post('https://192.168.8.160:8443/maxkey/api/oauth/v20/me',array(
                    "access_token"=>$accessToken->getToken()
                ));
                var_dump($res);

                
        
            } catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e) {
        
                // Failed to get the access token or user details.
                exit($e->getMessage());
        
            }
        
        }
    }



}