<?php

    namespace Reflect\Database;

    use libmysqldriver\MySQL;

    use Reflect\ENV;

    class Database extends MySQL {
        public const MYSQL_INT_MAX_SIZE = 32;
        public const MYSQL_VARCHAR_MAX_SIZE = 255;

        public function __construct() {
            parent::__construct(
                ENV::get(ENV::MYSQL_HOST),
                ENV::get(ENV::MYSQL_USER),
                ENV::get(ENV::MYSQL_PASS),
                ENV::get(ENV::MYSQL_DB)
            );
        }
    }