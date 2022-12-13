<?php

    require_once Path::api();
    require_once Path::src("database/Auth.php");

    class _ReflectSessionUser extends API {
        public static $rules = [
            "active" => [
                "required" => true,
                "type"     => "bool"
            ]
        ];

        public function __construct() {
            parent::__construct(ContentType::JSON);
            $this->db = new AuthDB(ConType::INTERNAL);
        }

        // Get order status by order reference
        public function _GET() {
            // Resolve user id from current key
            $sql = "SELECT user FROM api_keys WHERE id = ?";
            $res = $this->db->return_array($sql, $this->db->get_api_key());

            // Current key is not in keys table.
            // This is probably a configuration error. The requester key had access in the ACL table
            // to make the call, but the corresponding key does not exist. Restore from backup and make
            // sure that foregin key constraints are enabled.
            if (empty($res)) {
                $this->stderr("Key error", 500, "Requester API key can not be found. This is a big problem");
            }

            // Flatten array
            $res = $res[0];

            // User is not active
            return $this->call("reflect/User?id={$res["user"]}")
                ? $this->stdout($res["user"])
                : $this->stderr("No user", 404, "API user is disabled or does not exist");
        }

        // Delete own user (by flagging it as inactive)
        public function _DELETE() {
            // Get user id from key
            $user_id = $this->call("reflect/session/User", Method::GET);
            // Trying to delete a Reflect default user will probably cause problems.
            // A default user can not be deleted from the session endpoints.
            if (empty($user_id) || $user_id === "HTTP_ANYONE") {
                return $this->stderr("Failed get user id", 422, $user_id);
            }

            // Attempt to delete own user
            $sql = "UPDATE api_users SET active = ? WHERE id = ?";
            $res = $this->db->return_bool($sql, [0, $user_id]);
            return !empty($res) 
                ? $this->stdout("OK") 
                : $this->stderr("Failed to delete user", 422, $res);
        }
    }