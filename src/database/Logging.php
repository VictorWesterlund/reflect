<?php

    class LogDB extends SQLiteDriver {
        public function __construct() {
            parent::__construct("log");
        }
    }