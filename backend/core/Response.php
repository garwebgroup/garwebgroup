<?php
/**
 * Response.php
 * Small helper to send consistent, safe JSON responses and stop execution.
 *
 * Error response shape:
 * {
 *   "success": false,
 *   "error": "Something went wrong. Please try again.",   // generic, always safe to show a user
 *   "request_id": "a1b2c3d4",                                // always present, for correlating with server logs
 *   "debug": { "type": "...", "message": "...", "file": "...", "line": 12 } // ONLY when DEBUG_API is true
 * }
 */

class Response
{
    private static ?string $requestId = null;

    public static function requestId(): string
    {
        if (self::$requestId === null) {
            self::$requestId = bin2hex(random_bytes(4)); // short id, e.g. "a1b2c3d4"
        }
        return self::$requestId;
    }

    public static function json(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        // Prevent the browser from trying to "sniff" content type
        header('X-Content-Type-Options: nosniff');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param string     $userMessage  Generic, user-safe message. Always shown.
     * @param int        $httpCode     HTTP status code.
     * @param array|null $debugDetails Developer-facing details (e.g. ['type'=>..., 'message'=>..., 'file'=>..., 'line'=>...]).
     *                                 Only ever included in the response when DEBUG_API is true; always logged server-side regardless.
     * @param array      $extra        Any additional top-level properties to merge in (e.g. ['field' => 'email']).
     */
    public static function error(string $userMessage, int $httpCode = 400, ?array $debugDetails = null, array $extra = []): void
    {
        $requestId = self::requestId();

        $payload = [
            'success'    => false,
            'error'      => $userMessage,
            'request_id' => $requestId,
        ];

        if ($debugDetails !== null) {
            // Always log full details server-side, tagged with the request id, regardless of DEBUG_API.
            error_log("[{$requestId}] " . json_encode($debugDetails));

            if (defined('DEBUG_API') && DEBUG_API) {
                $payload['debug'] = $debugDetails;
            }
        }

        if (!empty($extra)) {
            $payload = array_merge($payload, $extra);
        }

        self::json($payload, $httpCode);
        exit;
    }

    public static function success(array $data = [], int $httpCode = 200): void
    {
        self::json(array_merge(['success' => true], $data), $httpCode);
        exit;
    }
}
