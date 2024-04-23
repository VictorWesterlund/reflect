<?php

    namespace Reflect\Database;

    use libmysqldriver\MySQL;

    use Reflect\ENV;
    use Reflect\Path;
    use Reflect\Method;
    use Reflect\Request\Connection;

    use Reflect\Database\Model\Acl\AclModel;
    use Reflect\Database\Model\Keys\KeysModel;
    use Reflect\Database\Model\Users\UsersModel;
    use Reflect\Database\Model\Endpoints\EndpointsModel;
    use Reflect\Database\Model\RelUsersGroups\RelUsersGroupsModel;

    require_once Path::reflect("src/database/model/AclModel.php");
    require_once Path::reflect("src/database/model/KeysModel.php");
    require_once Path::reflect("src/database/model/UsersModel.php");
    require_once Path::reflect("src/database/model/EndpointsModel.php");
    require_once Path::reflect("src/database/model/RelUsersGroupsModel.php");

    class Database extends MySQL {
        private const EMPTY_KEY = "NULL";

        protected static ?string $api_key;
        protected static ?string $user_id;
        protected static array $user_groups;

        private Connection $con;

        public function __construct(Connection $con) {
            parent::__construct(
                ENV::get(ENV::MYSQL_HOST),
                ENV::get(ENV::MYSQL_USER),
                ENV::get(ENV::MYSQL_PASS),
                ENV::get(ENV::MYSQL_DB)
            );

            $this->con = $con;

            self::$api_key = self::get_key_from_berer();
            self::$user_id = $this->get_user_id();
            self::$user_groups = $this->get_user_groups();
        }

        // Get key from Authorization header
        private static function get_key_from_berer(): ?string {
            // No API key provided
            if (empty($_SERVER["HTTP_AUTHORIZATION"])) {
                // Mock Authorization header
                $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . self::EMPTY_KEY;
            }

            // Destruct Authorization header from <auth-scheme> <authorization-parameters>
            [$scheme, $key] = explode(" ", $_SERVER["HTTP_AUTHORIZATION"], 2);

            // Return berar token from Authorization header
            return $scheme === "Bearer" || $key !== self::EMPTY_KEY ? $key : null;
        }

        // Returns true if the provided endpoint string is active
        private function endpoint_active(string $endpoint): bool {
            return $this->for(EndpointsModel::TABLE)
                ->where([
                    EndpointsModel::ID->value     => $endpoint,
                    EndpointsModel::ACTIVE->value => 1
                ])
                ->limit(1)
                ->select(null)->num_rows === 1;
        }

        // Return bool user id is enabled
        private function user_active(string|null $user): bool {
            // Internal connections have no API key, so return true
            if ($this->con === Connection::INTERNAL) {
                return true;
            }

            // Return true if user exists and is active
            return $this->for(UsersModel::TABLE)
                ->where([
                    UsersModel::ID->value     => $user,
                    UsersModel::ACTIVE->value => true
                ])
                ->limit(1)
                ->select(null)->num_rows === 1;
        }

        // Check if key and its user is active and not expired
        private function get_user_id(): ?string {
            // No key has been set
            if (!self::$api_key) {
                return null;
            }
            
            // Values for SQL sprintf
            $fvalues = [
                KeysModel::REF_USER->value,
                KeysModel::TABLE,
                KeysModel::ID->value,
                KeysModel::ACTIVE->value,
                KeysModel::EXPIRES->value
            ];

            // Return user id for key if it exists and is not expired
            // NOTE: libmysqldriver\MySQL does not implement range operators (yet), so the questy string has to be built manually
            $sql = "SELECT `%s` FROM `%s` WHERE `%s` = ? AND `%s` = 1  AND (NOW() BETWEEN NOW() AND FROM_UNIXTIME(COALESCE(`%s`, UNIX_TIMESTAMP())))";
            $res = $this->exec(sprintf($sql, ...$fvalues), self::$api_key);

            // Key is not active or invalid
            if ($res->num_rows !== 1) {
                return null;
            }

            $user_id = $res->fetch_assoc()[KeysModel::REF_USER->value];
            
            // Return user id from key or null if user is inactive
            return $this->user_active($user_id) ? $user_id : null;
        }

        // Return list of all group names associated with the current user
        private function get_user_groups(): array {
            // No groups if user is anonymous/public
            if (!self::$api_key) {
                return [];
            }

            $resp = $this->for(RelUsersGroupsModel::TABLE)
                ->where([
                    RelUsersGroupsModel::REF_USER->value => self::$user_id
                ])
                ->select(RelUsersGroupsModel::REF_GROUP->value);

            // Extract group ID from database response
            return array_column($resp->fetch_all(MYSQLI_ASSOC), RelUsersGroupsModel::REF_GROUP->value);
        }

        // Return array of request methods available on $endpoint to current user
        public function get_options(string $endpoint): array {
            $methods = [];

            // Check each method
            foreach (Method::cases() as $method) {
                // Skipping OPTIONS will make it easier to test "no-access" against an empty array downstream
                if ($method === Method::OPTIONS) continue;

                if ($this->has_access($endpoint, $method)) {
                    $methods[] = $method->value;
                }
            }
            
            return $methods;
        }

        // Check if API key is authorized to call endpoint using method
        public function has_access(string $endpoint, Method $method): bool {
            // ACL WHERE clause for public endpoints (group = NULL)
            $public_endpoint = [
                AclModel::REF_GROUP->value    => null,
                AclModel::REF_ENDPOINT->value => $endpoint,
                AclModel::METHOD->value       => $method->value
            ];

            // Return false if endpoint is disabled or invalid
            if (!$this->endpoint_active($endpoint)) {
                return false;
            }

            // Internal and connections are always allowed if the endpoint is active
            if ($this->con === Connection::INTERNAL) {
                return true;
            }

            // No API key provided, or user is dissabled. Check if the endpoint is public
            if (!self::$api_key || !self::$user_id) {
                return $this->for(AclModel::TABLE)
                    ->where($public_endpoint)
                    ->limit(1)
                    ->select(null)->num_rows === 1;
            }

            // Build ACL conditions for each group the user is a member of
            $group_queries = [];
            foreach ($this->get_user_groups() as $group_name) {
                $group_queries[] = [
                    AclModel::REF_GROUP->value    => $group_name,
                    AclModel::REF_ENDPOINT->value => $endpoint,
                    AclModel::METHOD->value       => $method->value
                ];

                // Include a check if the endpoint is public
                $group_queries[] = $public_endpoint;
            }

            // Return true if the user has access to endpoint and method through group id
            return $this->for(AclModel::TABLE)
                ->where(...$group_queries)
                ->limit(1)
                ->select(AclModel::REF_GROUP->value)->num_rows === 1;
        }
    }