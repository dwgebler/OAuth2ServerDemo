<?php

namespace Sample;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

require_once __DIR__ . '/vendor/autoload.php';

// Simple app class with a couple of endpoints to simulate an OAuth2 client.
// Run via `php -S localhost:8080 app.php` from this directory.
// Create the OAuth2 Client from the Symfony console:
// bin/console league:oauth2-server:create-client "Test Client" testclient testpass --scope=email --scope=profile --scope=blog_read --grant-type=refresh_token --grant_type=authorization_code --redirect-uri=http://localhost:8080/callback
class App
{
    private string $htmlTemplate = '';

    private string $clientId = 'testclient';
    private string $clientSecret = 'testpass';
    private string $redirectUri = 'http://localhost:8080/callback';
    private string $authServer = 'http://localhost:8000/authorize';
    private string $tokenServer = 'http://localhost:8000/token';
    private string $jwksUri = 'http://localhost:8000/.well-known/jwks.json';
    private string $apiUri = 'http://localhost:8000/api/test';

    public function __construct()
    {
        $this->htmlTemplate = file_get_contents(__DIR__ . '/template.html');
    }

    private function getRequestPath(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        return parse_url($requestUri, PHP_URL_PATH);
    }

    private function render(string $content)
    {
        $html = str_replace('[CONTENT]', $content, $this->htmlTemplate);
        echo $html;
        exit;
    }

    public function run()
    {
        $requestPath = $this->getRequestPath();
        switch ($requestPath) {
            case '/':
                $this->indexAction();
                break;
            case '/login':
                $this->loginAction();
                break;
            case '/callback':
                $this->callbackAction();
                break;
            case '/api':
                $this->apiAction();
                break;
            case '/logout':
                $this->logoutAction();
                break;
            default:
                $this->notFoundAction();
        }
    }

    private function notFoundAction()
    {
        http_response_code(404);
        echo 'Not found';
    }

    private function loginAction()
    {
        // Redirect to the authorization server.
        $params = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => 'blog_read profile email',
        ];
        $url = $this->authServer . '?' . http_build_query($params);
        header('Location: ' . $url);
    }

    private function indexAction()
    {
        $content = '';
        if (isset($_COOKIE['access_token'])) {
            $content = '<p><a href="/api">Call API</a></p><p><a href="/logout">Logout</a></p>';
        }
        $this->render($content);
    }

    private function callbackAction()
    {
        $code = $_GET['code'] ?? null;
        if (null === $code) {
            $content = 'No code provided<br>';
            if (isset($_GET['error_description'])) {
                $content .= 'Error: ' . $_GET['error_description'] . '<br>';
            }
            $content .= '<a href="/">Back</a>';
            $this->render($content);
        }
        // Swap the code for an access token.
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ];
        $ch = curl_init($this->tokenServer);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Ignore SSL for demo purposes.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        $accessToken = $response['access_token'] ?? null;
        $content = '';
        if (!$accessToken) {
            $content = 'No access token provided<br>';
            if (isset($response['hint'])) {
                $content .= 'Error: ' . $response['hint'] . '<br>';
            }
            $content .= '<a href="/">Back</a>';
            $this->render($content);
        }
        try {
            $streamContext = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]);
            $jwks = JWK::parseKeySet(json_decode(file_get_contents($this->jwksUri, context: $streamContext), true));
            JWT::$leeway = 10;
            JWT::decode($accessToken, $jwks[1]);
        } catch (\Exception $e) {
            $content = 'Error decoding JWT: ' . $e->getMessage();
            $this->render($content);
        }
        // Save the access token in a cookie.
        setcookie('access_token', $accessToken, time() + 3600);
        // Redirect to the home page.
        header('Location: /');
    }

    private function apiAction()
    {
        // Get the access token from the cookie.
        $accessToken = $_COOKIE['access_token'] ?? null;
        if (null === $accessToken) {
            $this->render('No access token provided');
        }
        // Call the API.
        $ch = curl_init($this->apiUri);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Ignore SSL for demo purposes.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        $content = '<p>Calling API on ' . $this->apiUri . '<br>';
        $content .= 'With access token ' . $accessToken . '</p>';
        $content .= 'Response: <div class="w3-code"><pre>' . print_r($response, true) . '</pre></div><br>';
        $content .= '<a href="/">Back</a>';
        $this->render($content);
    }

    private function logoutAction()
    {
        setcookie('access_token', '', time() - 3600);
        header('Location: /');
    }
}

// Eugh, blame Firebase::JWT for this.
error_reporting(E_ALL & ~E_DEPRECATED);

$app = new App();
$app->run();