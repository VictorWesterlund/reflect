<?php

    require_once Path::src("database/drivers/MariaDB.php");

    class LAMSdb3 extends MariaDBDriver {
        // This is the DATETIME format used for all
        // date fields in LAMSdb3.
        public static $date_format = "Y-m-d H:i:s";

        public function __construct() {
            parent::__construct($_ENV["db"]["lamsdb3"]);
        }

        // Create Prepared Statement for UPDATE 
        public function update(string $table, array $fields, string|int $key): bool {
            // Create CSV string with Prepared Statement abbreviations
            // from length of fields array.
            $changes = array_map(fn($column) => "${column} = ?", array_keys($fields));
            $changes = implode(",", $changes);

            $sql = "UPDATE ${table} SET ${changes} WHERE id = ?";

            // Append key to array of values for Prepared Statement.
            // This is the value for "id" in the SQL WHERE clause.
            $values = array_values($fields);
            $values[] = $key;

            return $this->return_bool($sql, $values);
        }

        // Create Prepared Statemt for INSERT
        public function insert(string $table, array $fields): bool {
            // Create CSV string of columns
            $columns = implode(",", array_keys($fields));
            // Return CSV string with Prepared Statement abbreviatons
            // from length of fields array.
            $values = implode(",", array_fill(0, count($fields), "?"));

            $sql = "INSERT INTO ${table} (${columns}) VALUES (${values})";
            return $this->return_bool($sql, array_values($fields));
        }
    }