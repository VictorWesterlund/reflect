<?php

    namespace Reflect\Database;

    use \Reflect\ENV;
    use \Reflect\Path;
    use \Reflect\Request\Connection;
    use \Reflect\Request\Method;

    use \Reflect\Database\Acl\Model as AclModel;
    use \Reflect\Database\Keys\Model as KeysModel;
    use \Reflect\Database\Users\Model as UsersModel;
    use \Reflect\Database\Endpoints\Model as EndpointsModel;

    use \libmysqldriver\MySQL as MySQLDriver;

    require_once Path::reflect("src/database/model/Acl.php");
    require_once Path::reflect("src/database/model/Keys.php");
    require_once Path::reflect("src/database/model/Users.php");
    require_once Path::reflect("src/database/model/Endpoints.php");

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
            return $this->for(UsersModel::TABLE)
                ->with(UsersModel::values())
                ->where([
                    UsersModel::ID->value     => $user,
                    UsersModel::ACTIVE->value => true
                ])
                ->limit(1)
                ->select(null);
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
            $user = KeysModel::USER->value;
            $table = KeysModel::TABLE;

            // Get column names from backed enum
            $col_id = KeysModel::ID->value;
            $col_active = KeysModel::ACTIVE->value;
            $col_created = KeysModel::CREATED->value;
            $col_expires = KeysModel::EXPIRES->value;

            $sql = "SELECT {$user} FROM {$table} WHERE {$col_id} = ? AND {$col_active} = 1 AND CURRENT_TIMESTAMP() BETWEEN {$col_created} AND COALESCE({$col_expires}, NOW())";
            $res = $this->exec($sql, $key);
            
            // Return key from request or default to anonymous key if it's invalid
            return !empty($res) && $this->user_active($res[0]["user"]) ? $key : $this->get_default_key();
        }

        // Return bool endpoint enabled
        public function endpoint_active(string $endpoint): bool {
            return $this->for(EndpointsModel::TABLE)
                ->with(EndpointsModel::values())
                ->where([
                    "endpoint" => $endpoint,
                    "active"   => 1
                ])
                ->limit(1)
                ->select(null);
        }

        // Return all available request methods to endpoint with key
        public function get_options(string $endpoint): array {
            $acl = $this->for(AclModel::TABLE)
                ->with(AclModel::values())
                ->where([
                    "api_key"  => $this->get_api_key(),
                    "endpoint" => $endpoint
                ])
                ->select(["method"]);
            
            // Flatten array to only values of "method"
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
            $has_access = $this->for(AclModel::TABLE)
                ->with(AclModel::values())
                ->where($filter)
                ->limit(1)
                ->select(null);

            // API key does not have access. So let's check again using the default public API key
            if (empty($has_access)) {
                $filter["api_key"] = $this->get_default_key();
                
                $has_access = $this->for(AclModel::TABLE)
                    ->with(AclModel::values())
                    ->where($filter)
                    ->limit(1)
                    ->select(null);
            }

            return !empty($has_access);
        }
    }