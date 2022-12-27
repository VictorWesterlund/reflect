<?php

    require_once Path::api();
    require_once Path::src("database/Auth.php");

    class _ReflectEndpoint extends API {
        public static $rules = [
            "endpoint" => [
                "required" => true,
                "type"     => "text",
                "max"      => 128
            ]
        ];

        public function __construct() {
            parent::__construct(ContentType::JSON);
            $this->db = new AuthDB(ConType::INTERNAL);
        }

        private static function uc_first_endpoint(string $endpoint): string|bool {
            // Split endpoint string into crumbs
            $crumbs = explode("/", $endpoint);
            // Endpoint must contain at least one slash
            if (count($crumbs) < 2) {
                return false;
            }

            // Capitalize first char of endpoint
            $key = array_key_last($crumbs);
            $crumbs[$key] = ucfirst($crumbs[$key]);

            // Reconstruct string
            return implode("/", $crumbs);
        }

        // Get endpoints
        public function _GET() {
            // Check if endpoint exists by name
            if (!empty($_GET["id"])) {
                $sql = "SELECT NULL FROM api_endpoints WHERE endpoint = ? AND active = 1";
                $res = $this->db->return_bool($sql, $_GET["id"]);

                return !empty($res) ? $this->stdout("OK") : $this->stderr("No endpoint", 404, "No endpoint found with name '{$_GET["id"]}'");
            }

            // Return array of all active Reflect API users
            $sql = "SELECT endpoint FROM api_endpoints WHERE active = 1";
            return $this->stdout($this->db->return_array($sql));
        }

        public function _PATCH() {
            // Array of columns to patch
            $update = [];

            if (!empty($_POST["endpoint"])) {
                $_POST["endpoint"] = $this::uc_first_endpoint($_POST["endpoint"]);
                if (empty($_POST["endpoint"])) {
                    return $this->stderr("Invalid endpoint", 400, "Endpoint name must contain scope and endpoint separated by a slash");
                }

                $update["endpoint"] = $_POST["endpoint"];
            }

            // Build SQL UPDATE CSV from array of values
            $columns = array_map(fn($column): string => "${column} = ?", array_keys($update));
            $columns = implode(",", $columns);

            $sql = "UPDATE api_endpoints SET ${columns} WHERE id = ?";
            // Create new array with id of target row appended to array of column values
            $res = $this->db->return_bool($sql, array_merge(
                array_values($update),
                [$_GET["id"]]
            ));

            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to update", 400, $res);
        }

        // Add new endpoint
        public function _POST() {
            $endpoint = $this::uc_first_endpoint($_POST["endpoint"]);
            if (empty($endpoint)) {
                return $this->stderr("Invalid endpoint", 400, "Endpoint name must contain scope and endpoint separated by a slash");
            }

            // Capitalize first char of endpoint
            $key = array_key_last($crumbs);
            $crumbs[$key] = ucfirst($crumbs[$key]);

            // Attempt to add endpoint
            $sql = "INSERT INTO api_endpoints (endpoint, active) VALUES (?, ?)";
            $res = $this->return_bool($sql, [
                $_POST["endpoint"],
                1
            ]);

            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to add endpoint", 500, $res);
        }

        // Delete endpoint by name
        public function _DELETE() {
            // Check that we got an id from the requester
            if (empty($_GET["id"])) {
                return $this->stderr("No id provided", 400, "Parameter id can not be empty");
            }

            // Soft-delete by deactivating endpoint by name
            $sql = "UPDATE api_endpoints SET active = 0 WHERE endpoint = ?";
            $res = $this->db->return_bool($sql, $_GET["id"]);
            return !empty($res) ? $this->stdout("OK") : $this->stderr("Failed to delete endpoint", 500, $res);
        }
    }