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

        private Connection $con;

        protected ?string $api_key = null;
        protected ?string $user_id = null;

        public function __construct(Connection $con) {
            parent::__construct(
                ENV::get(ENV::MYSQL_HOST),
                ENV::get(ENV::MYSQL_USER),
                ENV::get(ENV::MYSQL_PASS),
                ENV::get(ENV::MYSQL_DB)
            );

            $this->con = $con;

            $this->api_key = self::get_key_from_request();

            if ($this->api_key) {
                $this->user_id = $this->get_user_id();
            }
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

        // Get key from Authorization header
        protected static function get_key_from_request(): ?string {
            // No API key provided
            if (empty($_SERVER["HTTP_AUTHORIZATION"])) {
                // Mock Authorization header
                $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . self::EMPTY_KEY;
            }

            // Destruct Authorization header from <auth-scheme> <authorization-parameters>
            [$scheme, $key] = explode(" ", $_SERVER["HTTP_AUTHORIZATION"], 2);

            // Invalid authorization scheme or empty key, treat request as public
            if ($scheme !== "Bearer" || $key === self::EMPTY_KEY) {
                return null;
            }
            
            // Return API key if user is active. Else return null and treat the request as public
            return $key ? $key : null;
        }

        // Check if key and its user is active and not expired
        protected function get_user_id(): ?string {
            // No key has been set
            if (!$this->api_key) {
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
            $res = $this->exec(sprintf($sql, ...$fvalues), $this->api_key);

            // Key is not active or invalid
            if ($res->num_rows !== 1) {
                return null;
            }

            $user_id = $res->fetch_assoc()[KeysModel::REF_USER->value];
            
            // Return user id from key or null if user is inactive
            return $this->user_active($user_id) ? $user_id : null;
        }

        // Return list of all group names associated with the current user
        protected function get_user_groups(): array {
            $resp = $this->for(RelUsersGroupsModel::TABLE)
                ->where([
                    RelUsersGroupsModel::REF_USER->value => $this->user_id
                ])
                ->select(RelUsersGroupsModel::REF_GROUP->value);

            // Extract group ID from database response
            return array_column($resp->fetch_all(MYSQLI_ASSOC), RelUsersGroupsModel::REF_GROUP->value);
        }

        // Return all available request methods to endpoint with key
        public function get_options(string $endpoint): array {
            $api_key = $this->get_api_key();

            $acl = $this->for(AclModel::TABLE)
                ->with(AclModel::values())
                ->where([
                    "api_key"  => $api_key,
                    "endpoint" => $endpoint
                ])
                // TODO: libmysqldriver
                ->limit(6)
                ->select(["method"]);
            
            // Flatten array to only values of "method"
            return !empty($acl) ? array_column($acl, "method") : [];
        }

        // Check if API key is authorized to call endpoint using method
        public function has_access(string $endpoint, Method $method): bool {
            // Return false if endpoint is disabled or invalid
            if (!$this->endpoint_active($endpoint)) {
                return false;
            }

            // Internal and connections are always allowed if the endpoint is active
            if ($this->con === Connection::INTERNAL) {
                return true;
            }

            // No API key provided, or user is dissabled. Check if the endpoint is public
            if (!$this->api_key || !$this->user_id) {
                return $this->for(AclModel::TABLE)
                    ->where([
                        AclModel::REF_GROUP->value    => null,
                        AclModel::REF_ENDPOINT->value => $endpoint,
                        AclModel::METHOD->value       => $method->value
                    ])
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
            }

            // Return true if the user has access to endpoint and method through group id
            return $this->for(AclModel::TABLE)
                ->where(...$group_queries)
                ->limit(1)
                ->select(AclModel::REF_GROUP->value)->num_rows === 1;
        }
    }