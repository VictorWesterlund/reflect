<?php

    namespace Reflect;

    class Stdout {
        public function __construct(mixed $output, int $flags = null) {


            isset($_ENV[ENV]["INTERNAL_STDOUT"])
                ? $this->stdout_socket()
                : $this->stdout_http();
        }

        private function stdout_internal() {
            return $output;
        }

        private function stdout_http(): never {
            http_response_code($code);
            exit($output);
        }

        private function stdout_socket() {
            return $_ENV[ENV]["SOCKET_STDOUT"]($output, $code);
        }
    }