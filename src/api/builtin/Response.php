<?php

    namespace Reflect;

    // Supported response MIME types
    enum ContentType: string {
        case JSON = "application/json";
        case TEXT = "text/plain";
    }

    // Create a new reponse that will automatically output to the correct, current, connection channel
    class Response {
        public function __construct(mixed $output, int $code = 200, ContentType $type = ContentType::JSON) {
            $this->output = $output;
            $this->type = $type;
            $this->code = $code;

            // Set Content-Type of response with MIME type from enum
            header("Content-Type: {$type->value}");

            if (isset($_ENV[ENV]["INTERNAL_STDOUT"])) {
                $this->stdout_internal();
            } else {
                if (isset($_ENV[ENV]["SOCKET_STDOUT"])) {
                    $this->stdout_socket();
                } else {
                    $this->stdout_http();
                }
            }
        }

        // Request is internal. We don't care about the code here since the data will be used first-hand
        private function stdout_internal() {
            return $this->output;
        }

        // Echo the output
        private function stdout_http(): never {
            http_response_code($this->code);

            match ($this->type) {
                ContentType::JSON => exit(json_encode($this->output)),
                ContentType::TEXT => exit($output)
            };
        }

        private function stdout_socket() {
            return $_ENV[ENV]["SOCKET_STDOUT"]($this->output, $this->code);
        }
    }