<?php

    namespace Reflect\Database;

    use \Reflect\ENV;
    use \Reflect\Path;
    use \Reflect\Request\Connection;
    use \Reflect\Request\Method;

    use \libmysqldriver\MySQL as MySQLDriver;

    class AuthDB extends MySQLDriver {
        private const DEFAULT_PUBLIC_API_KEY = "PUBLIC_API_KEY";

        public function __construct(private Connection $con) {
            parent::__construct(
                ENV::get("mysql_host"),
                ENV::get("mysql_user"),
                ENV::get("mysql_pass"),
                ENV::get("mysql_db")
            );
        }

        // Return the API key to use for public/anonymous requests
        private function get_default_key(): string {
            return ENV::get("public_api_key") ?? self::DEFAULT_PUBLIC_API_KEY;
        }

        /* ---- */

        // Return bool user id is enabled
        public function user_active(string $user): bool {
            $sql = "SELECT NULL FROM api_users WHERE id = ? AND active = 1";
            return $this->return_bool($sql, $user);
        }

        // Validate API key from GET parameter
        public function get_api_key(): string {
            // Internal connections have no API key, so return empty string
            if ($this->get_default_key() === Connection::INTERNAL) {
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
            $sql = "SELECT user FROM api_keys WHERE id = ? 
            AND active = 1 AND CURRENT_TIMESTAMP() BETWEEN `created` AND COALESCE(`expires`, NOW())";

            $res = $this->return_array($sql, $key);
            
            // Return key from request or default to anonymous key if it's invalid
            return !empty($res) && $this->user_active($res[0]["user"]) ? $key : $this->get_default_key();
        }

        // Return bool endpoint enabled
        public function endpoint_active(string $endpoint): bool {
            $sql = "SELECT NULL FROM api_endpoints WHERE endpoint = ? AND active = 1 LIMIT 1";
            return $this->return_bool($sql, $endpoint);
        }

        // Return all available request methods to endpoint with key
        public function get_options(string $endpoint): array {
            $sql = "SELECT method FROM api_acl WHERE api_key = ? AND endpoint = ?";
            $res = $this->return_array($sql, [ $this->get_api_key(), $endpoint ]);
            
            // Flatten array to only values of "method"
            return !empty($res) ? array_column($res, "method") : [];
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

            // Get API key from request
            $key = $this->get_api_key();

            // Check if the API key has access to the requested endpoint and method
            $sql = "SELECT NULL FROM api_acl WHERE api_key = ? AND endpoint = ? AND method = ? LIMIT 1";
            $res = $this->return_bool($sql, [
                $key,
                $endpoint,
                $method->value
            ]);

            // API key does not have access. So let's check again using the default public API key
            if (empty($res)) {
                $res = $this->return_bool($sql, [
                    $this->get_default_key(),
                    $endpoint,
                    $method->value
                ]);
            }

            return !empty($res);
        }
    }