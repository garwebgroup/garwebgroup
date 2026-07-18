<?php
/**
 * CrudService.php
 * Generic, whitelisted CRUD engine (item #8 in the assignment brief).
 *
 * Why a whitelist instead of accepting arbitrary table/column names from the client?
 *  - Table and column names CANNOT be bound as PDO parameters (only values can be).
 *    If we ever interpolated a client-supplied table/column name into SQL, that would
 *    be a direct SQL injection vector. So every table and every column name is checked
 *    against a fixed server-side whitelist below before it ever touches a query string.
 *  - All *values* (data being inserted/updated/filtered) are always bound as parameters.
 */

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/RBAC.php';
require_once __DIR__ . '/Response.php';

class CrudService
{
    private PDO $db;

    /**
     * Whitelist: table => [
     *   'primary_key' => string,
     *   'columns'     => [column_name => 'type'],   // type used for light validation
     *   'editable'    => [column names accepted on create/update]
     * ]
     */
    private const SCHEMA = [
        'employees' => [
            'primary_key' => 'id',
            'columns' => [
                'id' => 'int', 'full_name' => 'string', 'position' => 'string',
                'department' => 'string', 'email' => 'string', 'phone' => 'string',
                'created_at' => 'string',
            ],
            'editable' => ['full_name', 'position', 'department', 'email', 'phone'],
        ],
        'company_profile' => [
            'primary_key' => 'id',
            'columns' => [
                'id' => 'int', 'company_name' => 'string', 'description' => 'string',
                'mission' => 'string', 'vision' => 'string', 'founded_year' => 'int',
                'logo_path' => 'string',
            ],
            'editable' => ['company_name', 'description', 'mission', 'vision', 'founded_year', 'logo_path'],
        ],
        'contact_messages' => [
            'primary_key' => 'id',
            'columns' => [
                'id' => 'int', 'name' => 'string', 'email' => 'string',
                'message' => 'string', 'created_at' => 'string',
            ],
            'editable' => ['name', 'email', 'message'],
        ],
        'users' => [
            'primary_key' => 'id',
            'columns' => [
                'id' => 'int', 'username' => 'string', 'email' => 'string',
                'full_name' => 'string', 'role' => 'string', 'created_at' => 'string',
                // password_hash intentionally excluded: never exposed via generic CRUD read
            ],
            'editable' => ['full_name', 'role'], // admin can edit name/role, not password here
        ],
    ];

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    private function assertTable(string $table): array
    {
        if (!isset(self::SCHEMA[$table])) {
            Response::error("Unknown table: {$table}", 400);
        }
        return self::SCHEMA[$table];
    }

    /** Keep only whitelisted, editable columns from client input. */
    private function filterEditableData(string $table, array $data): array
    {
        $schema = self::SCHEMA[$table];
        $clean = [];
        foreach ($schema['editable'] as $col) {
            if (array_key_exists($col, $data)) {
                $clean[$col] = Security::sanitizeString((string) $data[$col]);
            }
        }
        return $clean;
    }

    public function create(string $table, array $data, string $role): array
    {
        $this->assertTable($table);
        RBAC::requirePermission($table, 'create');

        $clean = $this->filterEditableData($table, $data);
        if (empty($clean)) {
            return ['success' => false, 'error' => 'No valid fields supplied'];
        }

        $columns = array_keys($clean);
        $placeholders = array_map(fn($c) => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->db->prepare($sql);
        $params = [];
        foreach ($clean as $col => $val) {
            $params[':' . $col] = $val;
        }
        $stmt->execute($params);

        return ['success' => true, 'id' => (int) $this->db->lastInsertId()];
    }

    /** Retrieve: single record by id, or a list with optional simple filters. */
    public function read(string $table, ?int $id, array $filters, string $role): array
    {
        $this->assertTable($table);
        RBAC::requirePermission($table, 'read');

        $schema = self::SCHEMA[$table];
        $pk = $schema['primary_key'];

        if ($id !== null) {
            $sql = sprintf('SELECT * FROM %s WHERE %s = :id LIMIT 1', $table, $pk);
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch();
            return ['success' => true, 'record' => $row ?: null];
        }

        // Whitelisted simple equality filters only (column must exist in schema).
        $whereParts = [];
        $params = [];
        foreach ($filters as $col => $val) {
            if (array_key_exists($col, $schema['columns'])) {
                $whereParts[] = "{$col} = :f_{$col}";
                $params[":f_{$col}"] = $val;
            }
        }

        $sql = "SELECT * FROM {$table}";
        if (!empty($whereParts)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }
        $sql .= " ORDER BY {$pk} DESC LIMIT 500"; // simple safety cap

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return ['success' => true, 'records' => $rows];
    }

    public function update(string $table, int $id, array $data, string $role): array
    {
        $this->assertTable($table);
        RBAC::requirePermission($table, 'update');

        $clean = $this->filterEditableData($table, $data);
        if (empty($clean)) {
            return ['success' => false, 'error' => 'No valid fields supplied'];
        }

        $schema = self::SCHEMA[$table];
        $pk = $schema['primary_key'];

        $setParts = array_map(fn($c) => "{$c} = :{$c}", array_keys($clean));
        $sql = sprintf('UPDATE %s SET %s WHERE %s = :id', $table, implode(', ', $setParts), $pk);

        $stmt = $this->db->prepare($sql);
        $params = [':id' => $id];
        foreach ($clean as $col => $val) {
            $params[':' . $col] = $val;
        }
        $stmt->execute($params);

        return ['success' => true, 'affected' => $stmt->rowCount()];
    }

    public function delete(string $table, int $id, string $role): array
    {
        $this->assertTable($table);
        RBAC::requirePermission($table, 'delete');

        $schema = self::SCHEMA[$table];
        $pk = $schema['primary_key'];

        $stmt = $this->db->prepare(sprintf('DELETE FROM %s WHERE %s = :id', $table, $pk));
        $stmt->execute([':id' => $id]);

        return ['success' => true, 'affected' => $stmt->rowCount()];
    }
}
