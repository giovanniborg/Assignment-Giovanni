<?php
require 'com/icemalta/kahuna/model/User.php';
require 'com/icemalta/kahuna/model/AccessToken.php';
require 'com/icemalta/kahuna/util/ApiUtil.php';
require 'com/icemalta/kahuna/model/Product.php';
use com\icemalta\kahuna\model\{User, AccessToken, Product};
use com\icemalta\kahuna\util\ApiUtil;

cors();

$endPoints = [];
$requestData = [];
header("Content-Type: application/json; charset=UTF-8");

/* BASE URI */
$BASE_URI = '/kahuna/api/';


function sendResponse(mixed $data = null, int $code = 200, mixed $error = null): void
{
    if (!is_null($data)) {
        $response['data'] = $data;
    }
    if (!is_null($error)) {
        $response['error'] = $error;
    }
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    http_response_code($code);
}

function checkToken(array $requestData): bool

{
    if (!isset($requestData['token']) || !isset($requestData['user'])) {
        return false;
    }
    
    $token = new AccessToken($requestData['user'], $requestData['token']);
    return AccessToken::verify($token);
}

/* Get Request Data */
$requestMethod = $_SERVER['REQUEST_METHOD'];
switch ($requestMethod) {
    case 'GET':
        $requestData = $_GET;
        break;
    case 'POST':
        $requestData = $_POST;
        break;
    case 'PATCH':
        parse_str(file_get_contents('php://input'), $requestData);
        ApiUtil::parse_raw_http_request($requestData);
        $requestData = is_array($requestData) ? $requestData : [];
        break;
    case 'DELETE':
        break;
    default:
        sendResponse(null, 405, 'Method not allowed.');
}

/* Extract EndPoint */
$parsedURI = parse_url($_SERVER["REQUEST_URI"]);
$path = explode('/', str_replace($BASE_URI, "", $parsedURI["path"]));
$endPoint = $path[0];
$requestData['dataId'] = isset($path[1]) ? $path[1] : null;
if (empty($endPoint)) {
    $endPoint = "/";
}

/* Extract Token */
if (isset($_SERVER["HTTP_X_API_KEY"])) {
    $requestData["user"] = $_SERVER["HTTP_X_API_USER"];
}
if (isset($_SERVER["HTTP_X_API_KEY"])) {
    $requestData["token"] = $_SERVER["HTTP_X_API_KEY"];
}

/* EndPoint Handlers */
$endpoints["/"] = function (string $requestMethod, array $requestData): void {
    sendResponse('Welcome to Kahuna API!');
};

$endpoints["user"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'POST') {
        $email = $requestData['email'];
        $password = $requestData['password'];
        $user = new User($email, $password);
        $user = User::save($user);
        sendResponse($user, 201);
    } else if ($requestMethod === 'PATCH') {
        sendResponse(null, 501, 'Updating a user has not yet been implemented.');
    } else if ($requestMethod === 'DELETE') {
        sendResponse(null, 501, 'Deleting a user has not yet been implemented.');
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};


$endpoints["login"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'POST') {
        $email = $requestData['email'];
        $password = $requestData['password'];
        $user = new User($email, $password);
        $user = User::authenticate($user);
        if ($user) {
            $token = new AccessToken($user->getId());
            $token = AccessToken::save($token);
            sendResponse([
                'user' => $user->getId(),
                 'token' => $token->getToken(),
                 'accessLevel' => $user->getAccessLevel()
                ]);
        } else {
            sendResponse(null, 401, 'Login failed.');
        }
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};

$endpoints["logout"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'POST') {
        if (checkToken($requestData)) {
            $userId = $requestData['user'];
            $token = new AccessToken($userId);
            $token = AccessToken::delete($token);
            sendResponse('You have been logged out.');
        } else {
            sendResponse(null, 403, 'Missing, invalid or expired token.');
        }
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};

$endpoints["token"] = function (string $requestMethod, array $requestData): void {
    if ($requestMethod === 'GET') {
        if (checkToken($requestData)) {
            sendResponse(['valid' => true, 'token' => $requestData['token']]);
        } else {
            sendResponse(['valid' => false, 'token' => $requestData['token']]);
        }
    } else {
        sendResponse(null, 405, 'Method not allowed.');
    }
};

$endpoints["product"] = function (string $requestMethod, array $requestData): void {
    if (checkToken($requestData)) {
        // User is logged in
        if ($requestMethod === 'GET') {
            $userId = $requestData['user'];
            if ($requestData['dataId']) {
                sendResponse(null, 501, 'Getting a specific Todo has not yet been implemented.');
            } else {
                $product = new Product($userId);
                $products = Product::load($product);
                sendResponse($products);
            }
        } elseif ($requestMethod === 'POST') {
            // Add a product
            $userId = $requestData['user']; 
            $serial = $requestData['serial'];
            $birth = $requestData['birth'] ?? 0; // Default value for birth if not provided
            $name = $requestData['name'];
            $warrantyLength = $requestData['warrantyLength'];
            $product = new Product($userId, $serial, $birth, $name, $warrantyLength);
            $product = Product::save($product);
            sendResponse($product, 201);
        } elseif ($requestMethod === 'PATCH') {
            // Edit a product
            $userId = $requestData['user']; 
            $serial = $requestData['serial'];
            $birth = $requestData['birth'] ?? 0;
            $name = $requestData['name'];
            $warrantyLength = $requestData['warrantyLength'];
            $id = $requestData['id'];
            $product = new Product($userId, $serial,$birth, $name, $warrantyLength);
            $product = Product::save($product);
            sendResponse($product);
        }elseif ($requestMethod === 'DELETE') {
            // Delete a product
            $userId = $requestData['user'];
            $productId = $requestData['dataId']; // Assuming 'dataId' contains the product ID to delete
            $product = new Product(userId: $userId, id: $productId);
            $success = Product::delete($product);
            if ($success) {
                sendResponse('Product deleted successfully.');
            } else {
                sendResponse(null, 500, 'Failed to delete product.');
            }
        } else {
            sendResponse(null, 405, 'Method not allowed.');
        }
        
    } else {
        // User is not logged in
        sendResponse(null, 403, 'Missing, invalid or expired token.');
    
    }        
};



$endpoints["404"] = function (string $requestMethod, array $requestData): void {
    sendResponse(null, 404, "Endpoint " . $requestData["endPoint"] . " not found.");
};

function cors()
{
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH, DELETE");

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }
}

try {
    if (isset($endpoints[$endPoint])) {
        $endpoints[$endPoint]($requestMethod, $requestData);
    } else {
        $endpoints["404"]($requestMethod, array("endPoint" => $endPoint));
    }
} catch (Exception $e) {
    sendResponse(null, 500, $e->getMessage());
} catch (Error $e) {
    sendResponse(null, 500, $e->getMessage());
}