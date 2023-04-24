<?php

    use libsqlitedriver\SQLite as SQLiteDriver;

    class IdempDb extends SQLiteDriver {

        // This is the name of the key in a JSON payload which contains
        // the idempotency UUID4.
        public static $key = "idempotency_key";

        public function __construct() {
            parent::__construct($this->get_db_name(), Path::reflect("src/database/init/IDEMP.sql"));
        }

        // Check if string is valid UUID4
        private static function is_uuid4(string $value): bool {
            $pattern = "/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i";
            return preg_match($pattern, $value);
        }

        // Generate a psuedo-random UUID4
        public static function uuidv4(): string {
            $bytes = random_bytes(16);

            $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40); // Set version to 0100
            $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80); // Set bytes 6-7 to 10

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }

        // Get path to SQLite database file
        private function get_db_name(): string {
            // Use CRC32 of concatinated environment variables as database name to store
            // used idempotency keys. We're using the mariadb values as if another endpoints
            // directory is used on the same machine, the database settings should change.
            $db = crc32(implode("", array_values($_ENV["mariadb"])));

            // Build path from root and database name with extension
            return "{$_ENV["idempotency"]}${db}.db";
        }

        // Returns true if a provided UUID exists in database
        public function check(string $uuid): bool {
            $sql = "SELECT NULL FROM keys WHERE uuid = ?";
            return $this->return_bool($sql, $uuid);
        }

        // Returns true if insert was successful, meaing that this
        // key has not been seen before due to the PRIMARY constraint on
        // the database column.
        public function set(string $uuid): bool|null {
            // Value must be a valid UUID4 string
            if (!IdempDb::is_uuid4($uuid)) {
                return null;
            }

            $sql = "INSERT INTO keys (uuid) VALUES (?)";
            return $this->return_bool($sql, $uuid);
        }
    }