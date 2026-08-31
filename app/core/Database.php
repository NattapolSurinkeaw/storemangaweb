<?php
/**
 * Database
 * PDO MySQL Singleton Connection
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host    = EnvLoader::get('DB_HOST', 'sql101.infinityfree.com');
            $dbname  = EnvLoader::get('DB_NAME', 'if0_42632739_mangainventory');
            $user    = EnvLoader::get('DB_USER', 'if0_42632739');
            $pass    = EnvLoader::get('DB_PASS', 'XAFJErfAAG4');
            $charset = EnvLoader::get('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }
}
