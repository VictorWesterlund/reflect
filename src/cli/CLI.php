<?php

    class CLI {
        public function __construct() {
            if (php_sapi_name() !== "cli") {
                die("Must be run from command line");
            }
        }

        public function echo(mixed $msg) {
            if (!is_string($msg)) {
                $msg = json_encode($msg);
            }

            echo $msg . "\n";
        }

        public function error(mixed $msg) {
            if (!is_string($msg)) {
                $msg = json_encode($msg);
            }

            return $this->echo("ERROR: " . $msg);
        }

        public function list(array $items) {
            foreach ($items as $item) {
                $this->echo($item);
            }
        }
    }