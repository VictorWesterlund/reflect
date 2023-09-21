<?php

    namespace Reflect;

    use \Reflect\ENV;

    // Create a new reponse that will automatically output to the correct, current, connection channel
    class Response {
        private const DEFAULT_TYPE = "application/json";

        public int $ok;
        public int $code;
        public string $type;

        private mixed $output;

        public function __construct(mixed $output, int $code = 200, ?string $type = null) {
            $this->code = $code;
            $this->output = $output;
            // MIME Type of the response
            $this->type = $type ? $type : self::DEFAULT_TYPE;
            
            // Similar to JavaScript's "Response.ok" for easy check if response is, well, OK.
            $this->ok = $code < 300 && $code >= 200;

            // Set Content-Type of response with MIME type from enum
            header("Content-Type: {$this->type}");

            // Response is not an internal request (from Call()) so we need to trigger an output from here
            if (!ENV::isset("INTERNAL_STDOUT")) {
                if (ENV::isset("SOCKET_STDOUT")) {
                    $this->stdout_socket();
                } else {
                    $this->stdout_http();
                }
            } else {
                $this->output();
            }
        }

        // Echo the output
        private function stdout_http(): never {
            http_response_code($this->code);
            
            // JSON encode output unless a custom MIME type has been specified
            exit($this->type === self::DEFAULT_TYPE ? json_encode($this->output) : $this->output);
        }

        // Pass output to socker handler
        private function stdout_socket() {
            return ENV::get("SOCKET_STDOUT")($this->output, $this->code);
        }

        // Get output for use with internal requests
        public function output(): mixed {
            return $this->output;
        }
    }