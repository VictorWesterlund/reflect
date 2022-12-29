<?php

    require_once Path::api();
    require_once Path::src("database/Auth.php");

    class _ReflectUser extends API {
        public static $rules = [
            "id" => [
                "required" => true,
                "type"     => "text"
            ],
            "active" => [
                "required" => false,
                "type"     => "bool"
            ]
        ];

        public function __construct() {
            parent::__construct(ContentType::JSON);
            $this->db = new AuthDB(ConType::INTERNAL);
        }

        // Get details of all active users
        public function _GET() {
            // Return bool if Reflect API user exists and is active by id
            if (!empty($_GET["id"])) {
                $sql = "SELECT id, active, created FROM api_users WHERE id = ?";
                $res = $this->db->return_array($sql, $_GET["id"]);
                return !empty($res) 
                    ? $this->stdout($res) 
                    : $this->stderr("No user", 404, "User does not exist");
            }

            // Return array of all active users
            $sql = "SELECT id, created FROM api_users WHERE active = 1";
            return $this->stdout($this->db->return_array($sql));
        }

        // Set active state of user
        public function _PUT() {
            $sql = "UPDATE api_users SET active = ? WHERE id = ?";
            $res = $this->db->return_bool($sql, [
                empty($_POST["active"]) ?: true,
                $_POST["id"]
            ]);

            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to add user", 500, $res);
        }

        // Create new user
        public function _POST() {
            $sql = "INSERT INTO api_users (id, active, created) VALUES (?, ?, ?)";
            $res = $this->db->return_bool($sql, [
                $_POST["id"],
                !empty($_POST["active"]) ? $_POST["active"] : 1,
                time()
            ]);

            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to add user", 500, $res);
        }

        // Delete user by id
        public function _DELETE() {
            // Check that we got an id from the requester
            if (empty($_GET["id"])) {
                return $this->stderr("No id provided", 400, "Parameter id can not be empty");
            }

            // Check that the user exists and is active
            $user = $this->call("reflect/User?id={$_GET["id"]}", Method::GET);
            if (!empty($user[0]["errorCode"]) && $user[0]["errorCode"] !== 404) {
                return $this->stderr("No user", 404, "User is inactive or does not exist");
            }

            // Soft-delete by deactivating user by id
            $sql = "UPDATE api_users SET active = 0 WHERE id = ?";
            $res = $this->db->return_bool($sql, $_GET["id"]);
            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to delete user", 500, $res);
        }
    }