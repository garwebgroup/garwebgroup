<?php
/**
 * Database.php
 * A tiny singleton wrapper around PDO.
 *
 * Security features:
 *  - PDO::ATTR_EMULATE_PREPARES = false  -> real prepared statements sent to MySQL,
 *    which is the main defense against SQL injection when combined with bound params.
 *  - PDO::ATTR_ERRMODE = EXCEPTION -> failures throw instead of failing silently.
 *  - Charset pinned to utf8mb4 to avoid certain encoding-based injection tricks.
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                // Never echo raw DB exceptions to the client in production (leaks schema/credentials info).
                Response::error(
                    'Unable to connect to the database. Please try again later.',
                    500,
                    [
                        'type'    => get_class($e),
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                    ]
                );
            }
        }

        return self::$instance;
    }
}
