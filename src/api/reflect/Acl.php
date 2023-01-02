<?php

    require_once Path::api();
    require_once Path::src("database/Auth.php");

    class _ReflectAcl extends API {
        public static $rules = [
            "api_key"  => [
                "required" => true,
                "max"      => 32
            ],
            "endpoint" => [
                "required" => true,
                "max"      => 32
            ],
            "method"   => [
                "required" => true,
                "type"     => "text"
            ]
        ];

        public function __construct() {
            parent::__construct(ContentType::JSON);
            $this->db = new AuthDB(ConType::INTERNAL);
        }

        // Get order status by order reference
        public function _GET() {
            // Return ACL details by id
            if (!empty($_GET["id"])) {
                $sql = "SELECT id, api_key, endpoint, method, created FROM api_acl WHERE id = ?";
                $res = $this->db->return_array($sql, $_GET["id"]);

                return !empty($res) ? $this->stdout($res) : $this->stderr("No record", 404, "No ACL record found with id '{$_GET["id"]}'");
            }

            // Return array of all active Reflect API users
            $sql = "SELECT id, created FROM api_acl";
            return $this->stdout($this->db->return_array($sql));
        }

        public function _POST() {
            // Check endpoint is valid
            $endpoint = $this->call("reflect/Endpoint?id={$_POST["endpoint"]}", Method::GET);
            if (!empty($endpoint["errorCode"])) {
                return $this->stderr("Unprocessable entity", 422, $endpoint);
            }

            // Check key is valid
            $key = $this->call("reflect/Key?id={$_POST["api_key"]}", Method::GET);
            if (!empty($key["errorCode"])) {
                return $this->stderr("Unprocessable entity", 422, $key);
            }

            // Make HTTP method uppercase
            $_POST["method"] = strtoupper($_POST["method"]);

            // Check method is in whitelist
            if (!Method::tryFrom($_POST["method"])) {
                return $this->stderr("Invalid HTTP method", 400, "'{$_POST["method"]}' is not a valid HTTP verb");
            }

            // Generate truncated SHA256 hash to 32 chars of of input fields
            $hash = substr(hash("sha256", implode("", [
                $_POST["api_key"],
                $_POST["endpoint"],
                $_POST["method"]
            ])), -32);

            // Check if ACL rule already exists
            if (empty($this->call("reflect/Acl?id=${hash}", Method::GET)["errorCode"])) {
                return $this->stderr("Already granted", 400, "This ACL rule has already been set");
            }

            $sql = "INSERT INTO api_acl (id, api_key, endpoint, method, created) VALUES (?, ?, ?, ?, ?)";
            $res = $this->db->return_bool($sql, [
                $hash,
                $_POST["api_key"],
                $_POST["endpoint"],
                $_POST["method"],
                time()
            ]);

            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to add ACL rule", 500, $res);
        }

        public function _DELETE() {
            if (empty($_GET["id"])) {
                $this->stderr("Bad request", 400, "No ACL id provided");
            }

            $sql = "DELETE FROM api_acl WHERE id = ?";
            $res = $this->db->return_bool($sql, $_GET["id"]);
            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to remove ACL rule", 500, $res);
        }
    }