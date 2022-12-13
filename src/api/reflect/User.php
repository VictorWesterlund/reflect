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

        // Get order status by order reference
        public function _GET() {
            // Return bool if Reflect API user exists and is active by id
            if (!empty($_GET["id"])) {
                return $this->db->user_active($_GET["id"]) 
                    ? $this->stdout("OK") 
                    : $this->stderr("No user", 404, "User is inactive or does not exist");
            }

            // Return array of all active Reflect API users
            $sql = "SELECT id, created FROM api_users WHERE active = 1";
            return $this->stdout($this->db->return_array($sql));
        }

        // Create new Reflect API user
        public function _POST() {
            $sql = "INSERT INTO api_users (id, active, created) VALUES (?, ?, ?)";
            return $this->stdout($sql, [
                $_POST["id"],
                !empty($_POST["active"]) ? $_POST["active"] : 1,
                time()
            ]);
        }
    }