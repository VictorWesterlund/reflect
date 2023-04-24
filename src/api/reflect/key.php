<?php

    require_once Path::init();
    require_once Path::reflect("src/database/Auth.php");

    class _ReflectKey extends API {
        public static $rules = [
            "id"      => [
                "required" => false,
                "type"     => "text",
                "min"      => 32,
                "max"      => 32
            ],
            "user"    => [
                "required" => true
            ],
            "active"  => [
                "required" => false,
                "type"     => "bool"
            ],
            "expires" => [
                "required" => false,
                "type"     => "int"
            ]
        ];
        
        public function __construct() {
            parent::__construct(ContentType::JSON);
            $this->db = new AuthDB(ConType::INTERNAL);
        }

        // Check that timestamp is not in the past from now
        private static function time_valid(int $key): bool {
            return $key > time();
        }

        // Get order status by order reference
        public function _GET() {
            $sql_time_clamp = "CURRENT_TIMESTAMP() BETWEEN `created` AND COALESCE(`expires`, NOW())";

            // Return bool if Reflect API user exists and is active by id
            if (!empty($_GET["id"])) {
                $sql = "SELECT id, active, user, expires, created FROM api_keys WHERE id = ?";
                $res = $this->db->return_array($sql, $_GET["id"]);

                if (empty($res)) {
                    return $this->stderr("Failed to get key data", 422, $res);
                }

                // Flatten array
                $res = $res[0];
                // Resolve user foregin key
                $res["user"] = $this->call("reflect/user?id={$res["user"]}")[0]["id"];

                return $this->stdout($res);
            }

            // Return array of all active Reflect API users
            $sql = "SELECT id, created, expires FROM api_keys WHERE active = 1 AND ${sql_time_clamp}";
            return $this->stdout($this->db->return_array($sql));
        }

        public function _PATCH() {
            // Array of columns to patch
            $update = [];

            if (empty($_GET["id"])) {
                return $this->stderr("No key selected", 404, "Param 'id' is required");
            }

            if (!empty($_POST["active"])) {
                if (!is_bool($_POST["active"])) {
                    return $this->stderr("Invalid data type", 400, "Expected field 'active' to be of type boolean");
                }

                $update["active"] = $_POST["active"];
            }

            if (!empty($_POST["user"])) {
                // Check that user exists and is active
                if (!$this->user_valid($_POST["user"])) {
                    return $this->stderr("No user", 404, "No Reflect user found with id '{$_POST["user"]}'");
                }

                $update["user"] = $_POST["user"];
            }

            // Check expiry date is greater than current timestamp
            if (!empty($_POST["expires"])) {
                if (!$this::time_valid($_POST["expires"])) {
                    return $this->stderr("Invalid expiry timestamp", 400, "Expiry UNIX timestamp must be greater than current time");
                }

                $update["expires"] = $_POST["expires"];
            }

            // Build SQL UPDATE CSV from array of values
            $columns = array_map(fn($column): string => "${column} = ?", array_keys($update));
            $columns = implode(",", $columns);

            $sql = "UPDATE api_keys SET ${columns} WHERE id = ?";
            // Create new array with id of target row appended to array of column values
            $res = $this->db->return_bool($sql, array_merge(
                array_values($update),
                [$_GET["id"]]
            ));

            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to update", 400, $res);
        }

        // Create new API key for user
        public function _POST() {
            // Check that the user exists and is active
            $user = $this->call("reflect/user?id={$_POST["user"]}", Method::GET);
            if (!empty($user[0]["errorCode"])) {
                return $this->stderr("Failed to add key", 500, $user);
            }

            // Derive key from a SHA256 hash of user id and current time
            // if no custom key is provided
            if (empty($_POST["id"])) {
                $_POST["id"] = substr(hash("sha256", implode("", [$_POST["user"], time()])), -32);
            }

            // Attempt to insert key
            $sql = "INSERT INTO api_keys (id, active, user, expires, created) VALUES (?, ?, ?, ?, ?)";
            $res = $this->db->return_bool($sql, [
                $_POST["id"],
                // Set active
                1,
                // Set user id
                $_POST["user"],
                // Set expiry timestamp if defined
                !empty($_POST["expires"]) ? $_POST["expires"] : null,
                // Set created timestamp
                time()
            ]);

            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to create key", 500, $res);
        }

        // Delete key by id
        public function _DELETE() {
            // Check that we got an id from the requester
            if (empty($_GET["id"])) {
                return $this->stderr("No id provided", 400, "Parameter id can not be empty");
            }

            // Check that the key exists and is active
            $key = $this->call("reflect/key?id={$_GET["id"]}", Method::GET);
            if (!empty($user[0]["errorCode"])) {
                return $this->stderr("Failed to add key", 500, $key);
            }

            // Soft-delete by deactivating key by id
            $sql = "UPDATE api_keys SET active = 0 WHERE id = ?";
            $res = $this->db->return_bool($sql, $_GET["id"]);
            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to delete key", 500, $res);
        }
    }