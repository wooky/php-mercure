<?php

declare(strict_types=1);

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Yakov\PhpMercure\Exception\ForbiddenException;
use Yakov\PhpMercure\Exception\InvalidRequestException;
use Yakov\PhpMercure\PhpMercureConfig;
use Yakov\PhpMercure\Publication\PublicationHandler;
use Yakov\PhpMercure\Subscription\SubscriptionHandler;
use Yakov\PhpMercure\Subscription\SubscriptionStreamingResponse;

require '../vendor/autoload.php';

$config = new PhpMercureConfig('!ChangeThisMercureHubJWTSecretKey!', '!ChangeThisMercureHubJWTSecretKey!', false);
$logger = new Logger('dev', [new StreamHandler('../dev.log')]);
$cookie = $_COOKIE['mercureAuthorization'];

$method = $_SERVER['REQUEST_METHOD'];
$query = $_SERVER['QUERY_STRING'];
$authorizationHeader = getAuthorizationHeader();
$logger->debug('Got request', [
    'method' => $method,
    'query' => $query,
    'authorizationHeader' => $authorizationHeader,
]);

try {
    switch ($method) {
        case 'GET':
            $subscriptionHandler = new SubscriptionHandler($config, $logger);
            $response = $subscriptionHandler->handleSubscriptionRequest($query, $authorizationHeader, $cookie);
            http_response_code(200);
            foreach (SubscriptionStreamingResponse::HEADERS as $key => $value) {
                header("{$key}: {$value}");
            }
            $response->serve();

            break;

        case 'POST':
            $publicationHandler = new PublicationHandler($config, $logger);
            $response = $publicationHandler->handlePublicationRequest($query, $authorizationHeader, $cookie);
            echo $response;

            break;

        default:
            throw new InvalidRequestException('Invalid request method');
    }
} catch (InvalidRequestException $e) {
    http_response_code(400);
    echo $e->getMessage();
} catch (ForbiddenException $e) {
    http_response_code(403);
    echo $e->getMessage();
} catch (\Throwable $th) {
    http_response_code(500);
    echo $th->getMessage();
}

function getAuthorizationHeader()
{
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER['Authorization']);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx or fast CGI
        $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        // print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    return $headers;
}