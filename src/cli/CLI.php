<?php

    namespace Reflect\CLI;

    use \Reflect\Path;
    use \Reflect\Response;

    require_once Path::reflect("src/api/builtin/Response.php");

    if (php_sapi_name() !== "cli") {
        die("Must be run from command line");
    }

    error_reporting(E_ALL ^ E_WARNING); 

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
            // Get message from
            if ($msg instanceof Response) {
                // Response is not OK. Show as error message
                if (!$msg->ok) {
                    return $this->error($msg->output());
                }

                $msg = $msg->output();
            }

            if (!is_string($msg)) {
                $msg = json_encode($msg);
            }

            echo $msg . "\n";
        }

        public function error(mixed $msg, string $expected = null) {
            if (!is_string($msg)) {
                $msg = json_encode($msg);
            }

            $this->echo("\e[41mERROR: ${msg}\e[0m");

            // Error has an expected format it wants to show
            if (!empty($expected)) {
                $this->echo("\e[91mExpected:\e[0m " . $expected);
            }
        }

        public function list(array $items) {
            foreach ($items as $item) {
                $this->echo($item);
            }
        }
    }