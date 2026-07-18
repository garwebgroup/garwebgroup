<?php
/**
 * RBAC.php
 * Simple role-based access control.
 *
 * Roles: 'admin', 'staff', 'user'
 *   admin -> full access to everything
 *   staff -> can manage company data (employees, company_profile) but not user accounts
 *   user  -> read-only on public company data, can submit contact messages
 *
 * The permission matrix maps: table => action => [roles allowed]
 * Actions: create | read | update | delete
 *
 * This is intentionally data-driven (not scattered if/else checks) so adding
 * a new table or role only means editing the matrix below.
 */

class RBAC
{
    private const MATRIX = [
        'employees' => [
            'create' => ['admin', 'staff'],
            'read'   => ['admin', 'staff', 'user'],
            'update' => ['admin', 'staff'],
            'delete' => ['admin'],
        ],
        'company_profile' => [
            'create' => ['admin'],
            'read'   => ['admin', 'staff', 'user'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
        'contact_messages' => [
            'create' => ['admin', 'staff', 'user'], // anyone can submit the contact form
            'read'   => ['admin', 'staff'],
            'update' => ['admin', 'staff'],
            'delete' => ['admin'],
        ],
        'users' => [
            'create' => ['admin'],
            'read'   => ['admin'],
            'update' => ['admin'],
            'delete' => ['admin'],
        ],
    ];

    public static function hasPermission(string $role, string $table, string $action): bool
    {
        if (!isset(self::MATRIX[$table][$action])) {
            return false; // unknown table/action -> deny by default
        }
        return in_array($role, self::MATRIX[$table][$action], true);
    }

    public static function isKnownTable(string $table): bool
    {
        return isset(self::MATRIX[$table]);
    }

    /** Ends the request with 403 if the current session role isn't in $allowedRoles. */
    public static function requireRole(array $allowedRoles): void
    {
        $role = $_SESSION['role'] ?? null;
        if ($role === null || !in_array($role, $allowedRoles, true)) {
            Response::error('Forbidden: insufficient permissions', 403);
        }
    }

    public static function requirePermission(string $table, string $action): void
    {
        $role = $_SESSION['role'] ?? null;
        if ($role === null || !self::hasPermission($role, $table, $action)) {
            Response::error('Forbidden: insufficient permissions', 403);
        }
    }
}
