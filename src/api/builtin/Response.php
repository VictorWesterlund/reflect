<?php

    namespace Reflect;

    use Reflect\ENV;

    // Create a new Reponse that will automatically output to the current connection channel
    class Response {
        private const DEFAULT_TYPE = "application/json";

        public int $ok;
        public int $code;
        public string $type;

        private mixed $output;

        public function __construct(mixed $output, int $code = 200, ?string $type = null) {
            $this->code = $code;
            $this->output = $output;

            // Set MIME of the Response
            $this->type = $type ? $type : self::DEFAULT_TYPE;
            
            // Response code is within HTTP Success range
            $this->ok = $code < 300 && $code >= 200;

            // Set Content-Type of response with MIME type from enum
            header("Content-Type: {$this->type}");

            // Response is not an internal request (from Call Reflect\Call) so we need to trigger an output from here
            ENV::isset(ENV::INTERNAL_STDOUT) ? $this->stdout_internal() : $this->stdout_http();
        }

        // Return output data directly. This method can be accessed from Reflect\Call
        public function output(): mixed {
            return $this->output;
        }

        // Write data to PHP's default standard output (HTTP response)
        private function stdout_http(): never {
            http_response_code($this->code);
            
            // JSON encode output unless a custom MIME type has been specified
            exit($this->type === self::DEFAULT_TYPE ? json_encode($this->output) : $this->output);
        }

        // Return Response to an internal request. Most likely from Reflect\Call
        private function stdout_internal(): mixed {
            // Set response envvar which lets downstream methods know we have a Response ready
            ENV::set(ENV::INTERNAL_STDOUT_RESP, [$this->output, $this->code]);

            return $this->output;
        }
    }