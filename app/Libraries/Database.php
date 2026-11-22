<?php

use CodeIgniter\Database\BaseConnection;

/**
 * Database — thin static wrapper around CodeIgniter's shared database connection.
 * Queries use "?" placeholders; CodeIgniter escapes every bound value.
 */
class Database
{
    private static ?BaseConnection $db = null;
    private static bool $inTransaction = false;

    private function __construct() {}

    public static function conn(): BaseConnection
    {
        if (self::$db === null) {
            $db = \Config\Database::connect();
            $db->initialize();
            $db->query("SET time_zone = '" . (new DateTime())->format('P') . "'");
            self::$db = $db;
        }
        return self::$db;
    }

    /** Run a query. Returns a result for SELECTs, true for writes. */
    public static function query(string $sql, array $params = [])
    {
        return self::conn()->query($sql, $params ?: null);
    }

    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->getResultArray();
    }

    public static function one(string $sql, array $params = []): ?array
    {
        return self::query($sql, $params)->getRowArray();
    }

    public static function value(string $sql, array $params = [])
    {
        $row = self::one($sql, $params);
        return $row ? reset($row) : null;
    }

    public static function lastId(): int
    {
        return (int) self::conn()->insertID();
    }

    // ----- Transactions -----
    public static function begin(): void
    {
        self::conn()->transBegin();
        self::$inTransaction = true;
    }

    public static function commit(): void
    {
        self::conn()->transCommit();
        self::$inTransaction = false;
    }

    /** Roll back if a transaction is still open. */
    public static function rollBack(): void
    {
        if (self::$inTransaction) {
            self::conn()->transRollback();
            self::$inTransaction = false;
        }
    }

    public static function inTransaction(): bool
    {
        return self::$inTransaction;
    }
}
