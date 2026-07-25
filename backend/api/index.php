<?php
/**
 * api/index.php
 * Single entry point (front controller) exposing the whole library to the
 * JavaScript frontend as a small JSON API.
 *
 * All requests go to this one file, e.g.:
 *   GET  /api/index.php?action=csrf                 -> get a CSRF token
 *   POST /api/index.php?action=register              -> { username, email, password, full_name }
 *   POST /api/index.php?action=login                  -> { identifier, password }
 *   POST /api/index.php?action=logout
 *   GET  /api/index.php?action=me                      -> current logged-in user
 *   GET  /api/index.php?action=crud&table=employees               -> list
 *   GET  /api/index.php?action=crud&table=employees&id=3           -> retrieve one
 *   POST /api/index.php?action=crud&table=employees                -> create   (JSON body)
 *   PUT  /api/index.php?action=crud&table=employees&id=3           -> update   (JSON body)
 *   DELETE /api/index.php?action=crud&table=employees&id=3         -> delete
 *
 * Every state-changing request (POST/PUT/DELETE) other than register/login
 * must include a valid CSRF token (fetched first via action=csrf) either as
 * body field "csrf_token" or header "X-CSRF-Token".
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Security.php';
require_once __DIR__ . '/../core/Response.php';
require_once __DIR__ . '/../core/RBAC.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/CrudService.php';

Security::startSecureSession();

// ---- CORS (only relevant if the frontend is on a different origin) ----
if (ALLOWED_ORIGIN !== '') {
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Parse JSON body for POST/PUT/DELETE (frontend should send Content-Type: application/json)
$body = [];
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    $raw = file_get_contents('php://input');
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $body = $decoded;
        }
    }
}

$stateChanging = in_array($method, ['POST', 'PUT', 'DELETE'], true);
$csrfExemptActions = ['login', 'register']; // no session/CSRF context to protect yet

if ($stateChanging && !in_array($action, $csrfExemptActions, true)) {
    $token = Security::extractCsrfToken($body);
    if (!Security::verifyCsrfToken($token)) {
        Response::error('Invalid or missing CSRF token', 403);
    }
}

try {
    switch ($action) {

        // ---------------- Utility ----------------
        case 'csrf':
            Response::success(['csrf_token' => Security::generateCsrfToken()]);
            break;

        // ---------------- Auth (item #7) ----------------
        case 'register':
            if ($method !== 'POST') Response::error('Method not allowed', 405);
            $auth = new AuthService();
            $result = $auth->register(
                $body['username'] ?? '',
                $body['email'] ?? '',
                $body['password'] ?? '',
                $body['full_name'] ?? ''
            );
            Response::json($result, $result['success'] ? 201 : 400);
            break;

        case 'login':
            if ($method !== 'POST') Response::error('Method not allowed', 405);
            $auth = new AuthService();
            $result = $auth->login($body['identifier'] ?? '', $body['password'] ?? '');
            Response::json($result, $result['success'] ? 200 : 401);
            break;

        case 'logout':
            if ($method !== 'POST') Response::error('Method not allowed', 405);
            (new AuthService())->logout();
            Response::success(['message' => 'Logged out']);
            break;

        case 'me':
            $user = (new AuthService())->currentUser();
            Response::success(['user' => $user]);
            break;

        // ---------------- CRUD (item #8) ----------------
        case 'crud':
            $auth = new AuthService();
            $user = $auth->requireAuth(); // must be logged in for any CRUD action
            $crud = new CrudService();
            $table = $_GET['table'] ?? '';
            $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

            if (!RBAC::isKnownTable($table)) {
                Response::error('Unknown or unauthorized table', 400);
            }

            switch ($method) {
                case 'GET':
                    $filters = $_GET;
                    unset($filters['action'], $filters['table'], $filters['id']);
                    $result = $crud->read($table, $id, $filters, $user['role']);
                    Response::json($result, 200);
                    break;

                case 'POST':
                    $result = $crud->create($table, $body, $user['role']);
                    Response::json($result, $result['success'] ? 201 : 400);
                    break;

                case 'PUT':
                    if ($id === null) Response::error('Missing id', 400);
                    $result = $crud->update($table, $id, $body, $user['role']);
                    Response::json($result, $result['success'] ? 200 : 400);
                    break;

                case 'DELETE':
                    if ($id === null) Response::error('Missing id', 400);
                    $result = $crud->delete($table, $id, $user['role']);
                    Response::json($result, $result['success'] ? 200 : 400);
                    break;

                default:
                    Response::error('Method not allowed', 405);
            }
            break;

        default:
            Response::error('Unknown action', 404);
    }
} catch (Throwable $e) {
    // Catch-all: user gets a generic message; developer gets structured details
    // (only when DEBUG_API is on); full details are always logged server-side either way.
    Response::error(
        'Something went wrong while processing your request. Please try again.',
        500,
        [
            'type'    => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ]
    );
}
