<?php

    namespace Reflect\CLI;

    if (php_sapi_name() !== "cli") {
        die("Must be run from command line");
    }

    class CLI {
        public function __construct(array $args, int $arglen) {
            // Get all CLI args except name of script file
            array_shift($args);
            $this->args = $args;

            // Pad array of arguments to expected length with nulls
            if (count($this->args) < $arglen) {
                $this->pad_args($arglen);
            }
        }

        // Add padding to argument array
        private function pad_args(int $target_arglen) {
            $arglen = count($this->args);
            $padding = $target_arglen - $arglen;

            // Fill remaining array slots with nulls
            $this->args = array_merge($this->args, array_fill($arglen, $padding, null));
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