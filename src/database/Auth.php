<?php

    namespace Reflect\Database;

    use \Reflect\ENV;
    use \Reflect\Path;
    use \Reflect\Request\Connection;
    use \Reflect\Request\Method;

    use \libmysqldriver\MySQL as MySQLDriver;

    class AuthDB extends MySQLDriver {
        public const DEFAULT_PUBLIC_API_KEY = "PUBLIC_API_KEY";

        private Connection $con;

        public function __construct(Connection $con) {
            parent::__construct(
                ENV::get("mysql_host"),
                ENV::get("mysql_user"),
                ENV::get("mysql_pass"),
                ENV::get("mysql_db")
            );

            $this->con = $con;
        }

        // Return the API key to use for public/anonymous requests
        private function get_default_key(): string {
            return ENV::get("public_api_key") ?? self::DEFAULT_PUBLIC_API_KEY;
        }

        /* ---- */

        // Return bool user id is enabled
        public function user_active(string|null $user): bool {
            // Internal connections have no API key, so return true
            if ($this->con === Connection::INTERNAL) {
                return true;
            }

            // Return true if user exists and is active
            return $this->get("api_users", null, [
                "id"     => $user,
                "active" => true
            ]);
        }

        // Validate API key from GET parameter
        public function get_api_key(): string {
            // Internal connections have no API key, so return empty string
            if ($this->con === Connection::INTERNAL) {
                return "";
            }

            // No "key" parameter provided so use anonymous key
            if (empty($_SERVER["HTTP_AUTHORIZATION"])) {
                // Mock Authorization header
                $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . $this->get_default_key();
            }

            // Destruct Authorization header from <auth-scheme> <authorization-parameters>
            [$scheme, $key] = explode(" ", $_SERVER["HTTP_AUTHORIZATION"], 2);

            // Default to public key if invalid scheme or is HTTP request but passed an internal key
            if ($scheme !== "Bearer") {
                return $this->get_default_key();
            }

            // Check that key exists, is active, and not expired (now > created && now < expires)
            $sql = "SELECT user FROM api_keys WHERE id = ? AND active = 1 AND CURRENT_TIMESTAMP() BETWEEN `created` AND COALESCE(`expires`, NOW())";
            $res = $this->exec($sql, $key);
            
            // Return key from request or default to anonymous key if it's invalid
            return !empty($res) && $this->user_active($res[0]["user"]) ? $key : $this->get_default_key();
        }

        // Return bool endpoint enabled
        public function endpoint_active(string $endpoint): bool {
            return $this->get("api_endpoints", null, [
                "endpoint" => $endpoint,
                "active"   => 1
            ], 1);
        }

        // Return all available request methods to endpoint with key
        public function get_options(string $endpoint): array {
            $filter = [
                "api_key"  => $this->get_api_key(),
                "endpoint" => $endpoint
            ];
            
            // Flatten array to only values of "method"
            $acl = $this->get("api_acl", ["method"], $filter, 1);
            return !empty($acl) ? array_column($acl, "method") : [];
        }

        // Check if API key is authorized to call endpoint using method
        public function check(string $endpoint, Method $method): bool {
            // Return false if endpoint is not enabled
            if (!$this->endpoint_active($endpoint)) {
                return false;
            }

            // Internal and connections are always allowed
            if ($this->con === Connection::INTERNAL) {
                return true;
            }

            // Prepare filter for ACL check
            $filter = [
                "api_key"  => $this->get_api_key(),
                "endpoint" => $endpoint,
                "method"   => $method->value
            ];

            // Check if the API key has access to the requested endpoint and method
            $has_access = $this->get("api_acl", null, $filter, 1);

            // API key does not have access. So let's check again using the default public API key
            if (empty($has_access)) {
                $filter["api_key"] = $this->get_default_key();
                
                $has_access = $this->get("api_acl", null, $filter, 1);
            }

            return !empty($has_access);
        }
    }