<?php
/**
 * Database Helper - Secure Query Builder
 * /helpers/Database.php
 *
 * Provides secure database operations using prepared statements.
 * Prevents SQL injection by automatically using parameterized queries.
 */

class Database {
    private $conn;

    /**
     * Constructor
     *
     * @param mysqli $connection MySQLi connection object
     */
    public function __construct($connection) {
        $this->conn = $connection;
    }

    /**
     * Secure SELECT query
     *
     * @param string $table Table name
     * @param array $where Associative array of conditions (column => value)
     * @param string $columns Columns to select (default: *)
     * @param string $orderBy Order by clause (e.g., "created_at DESC")
     * @param int $limit Limit number of results
     * @return mysqli_result Query result
     */
    public function select($table, $where = [], $columns = '*', $orderBy = null, $limit = null) {
        $sql = "SELECT $columns FROM $table";

        if (!empty($where)) {
            $conditions = array_keys($where);
            $placeholders = array_map(function($col) {
                return "$col = ?";
            }, $conditions);
            $sql .= " WHERE " . implode(' AND ', $placeholders);
        }

        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }

        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Database prepare failed: " . $this->conn->error);
            throw new Exception("Database query failed");
        }

        if (!empty($where)) {
            $types = $this->getTypes(array_values($where));
            $stmt->bind_param($types, ...array_values($where));
        }

        if (!$stmt->execute()) {
            error_log("Database execute failed: " . $stmt->error);
            throw new Exception("Database query failed");
        }

        return $stmt->get_result();
    }

    /**
     * Secure INSERT query
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int Insert ID of the new row
     */
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');

        $sql = "INSERT INTO $table (" . implode(', ', $columns) . ")
                VALUES (" . implode(', ', $placeholders) . ")";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Database prepare failed: " . $this->conn->error);
            throw new Exception("Database insert failed");
        }

        $types = $this->getTypes(array_values($data));
        $stmt->bind_param($types, ...array_values($data));

        if (!$stmt->execute()) {
            error_log("Database execute failed: " . $stmt->error);
            throw new Exception("Database insert failed");
        }

        return $this->conn->insert_id;
    }

    /**
     * Secure UPDATE query
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value to update
     * @param array $where Associative array of conditions (column => value)
     * @return bool True on success
     */
    public function update($table, $data, $where) {
        $set = array_map(function($col) {
            return "$col = ?";
        }, array_keys($data));

        $conditions = array_map(function($col) {
            return "$col = ?";
        }, array_keys($where));

        $sql = "UPDATE $table SET " . implode(', ', $set) .
               " WHERE " . implode(' AND ', $conditions);

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Database prepare failed: " . $this->conn->error);
            throw new Exception("Database update failed");
        }

        $values = array_merge(array_values($data), array_values($where));
        $types = $this->getTypes($values);
        $stmt->bind_param($types, ...$values);

        if (!$stmt->execute()) {
            error_log("Database execute failed: " . $stmt->error);
            throw new Exception("Database update failed");
        }

        return true;
    }

    /**
     * Secure DELETE query
     *
     * @param string $table Table name
     * @param array $where Associative array of conditions (column => value)
     * @return bool True on success
     */
    public function delete($table, $where) {
        $conditions = array_map(function($col) {
            return "$col = ?";
        }, array_keys($where));

        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $conditions);

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Database prepare failed: " . $this->conn->error);
            throw new Exception("Database delete failed");
        }

        $types = $this->getTypes(array_values($where));
        $stmt->bind_param($types, ...array_values($where));

        if (!$stmt->execute()) {
            error_log("Database execute failed: " . $stmt->error);
            throw new Exception("Database delete failed");
        }

        return true;
    }

    /**
     * Execute raw query with prepared statement
     *
     * @param string $sql SQL query with placeholders
     * @param array $params Array of parameters to bind
     * @return mysqli_result Query result
     */
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            error_log("Database prepare failed: " . $this->conn->error);
            throw new Exception("Database query failed");
        }

        if (!empty($params)) {
            $types = $this->getTypes($params);
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            error_log("Database execute failed: " . $stmt->error);
            throw new Exception("Database query failed");
        }

        $result = $stmt->get_result();

        // For queries that don't return results (INSERT, UPDATE, DELETE without RETURNING)
        if ($result === false) {
            return true;
        }

        return $result;
    }

    /**
     * Get parameter types for bind_param
     *
     * @param array $values Array of values to get types for
     * @return string String of type characters (i, d, s)
     */
    private function getTypes($values) {
        $types = '';
        foreach ($values as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        return $types;
    }

    /**
     * Get the underlying mysqli connection
     *
     * @return mysqli The connection object
     */
    public function getConnection() {
        return $this->conn;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->conn->begin_transaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->conn->rollback();
    }
}
?>
