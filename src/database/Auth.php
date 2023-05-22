<?php

    namespace Reflect\Database;

    use const \Reflect\ENV;
    use \Reflect\Path;
    use \Reflect\Request\Connection;
    use \Reflect\Request\Method;

    use \libmysqldriver\MySQL as MySQLDriver;

    class AuthDB extends MySQLDriver {
        // This is the default fallback key used when no key is provided
        // with the request (anonymous) and when a provided key lacks
        // access to a particular resource (forbidden).
        public static $key_default = "HTTP_ANYONE_KEY";
        // This key is used for internal requests.
        // I.e request using API->call() or Reflect's meta-endpoints.
        public static $key_internal = "INTERNAL";

        public function __construct(private Connection $con) {
            parent::__construct(
                $_ENV[ENV]["mysql_host"],
                $_ENV[ENV]["mysql_user"],
                $_ENV[ENV]["mysql_pass"],
                $_ENV[ENV]["mysql_db"]
            );
        }

        // Return bool user id is enabled
        public function user_active(string $user): bool {
            $sql = "SELECT NULL FROM api_users WHERE id = ? AND active = 1";
            return $this->return_bool($sql, $user);
        }

        // Validate API key from GET parameter
        public function get_api_key(): string {
            // No "key" parameter provided so use anonymous key
            if (empty($_SERVER["HTTP_AUTHORIZATION"])) {
                // Mock Authorization header
                $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . AuthDB::$key_default;
            }

            // Destruct Authorization header from <auth-scheme> <authorization-parameters>
            [$scheme, $key] = explode(" ", $_SERVER["HTTP_AUTHORIZATION"], 2);

            // Default to anonymos key if invalid scheme
            if ($scheme !== "Bearer") {
                return AuthDB::$key_default;
            }

            // Check that key exists, is active, and not expired (now > created && now < expires)
            $sql = "SELECT user FROM api_keys WHERE id = ? 
            AND active = 1 AND CURRENT_TIMESTAMP() BETWEEN `created` AND COALESCE(`expires`, NOW())";

            $res = $this->return_array($sql, $key);
            
            // Return key from request or default to anonymous key if it's invalid
            return !empty($res) && $this->user_active($res[0]["user"]) ? $key : AuthDB::$key_default;
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
            // Ensure endpoint is enabled
            if (!$this->endpoint_active($endpoint)) {
                return false;
            }

            // Internal and local connections are always allowed
            if (in_array($this->con, [Connection::INTERNAL, Connection::AF_UNIX])) {
                return true;
            }

            // Get user API key
            $key = $this->get_api_key();

            // Check if the API key has access to the requested endpoint and method
            $sql = "SELECT NULL FROM api_acl WHERE api_key = ? AND endpoint = ? AND method = ? LIMIT 1";
            $res = $this->return_bool($sql, [
                $key,
                $endpoint,
                $method->value
            ]);

            // API key does not have access. So let's check if the endpoint is public
            if (empty($res) && !in_array($key, [AuthDB::$key_default, AuthDB::$key_internal])) {
                $res = $this->return_bool($sql, [
                    AuthDB::$key_default,
                    $endpoint,
                    $method->value
                ]);
            }

            return !empty($res);
        }
    }