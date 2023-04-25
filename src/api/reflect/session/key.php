<?php

    require_once Path::init();
    require_once Path::reflect("src/database/Auth.php");

    class _ReflectSessionKey extends API {
        public function __construct() {
            parent::__construct(ContentType::JSON);
            $this->db = new AuthDB(Connection::INTERNAL);
        }

        // Get order status by order reference
        public function _GET() {
            return $this->stdout($this->db->get_api_key());
        }
    }