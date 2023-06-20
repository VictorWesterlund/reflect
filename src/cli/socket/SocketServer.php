<?php

    namespace Reflect\Socket;

    use \Reflect\ENV;
    use \Reflect\Path;
    use \Reflect\Request\Router;
    use \Reflect\Request\Connection;

    require_once Path::reflect("src/request/Router.php");

    // Handle RESTful requests over AF_UNIX socket.
    class SocketServer {
        // Socket transaction header size in bytes
        const HEADER_LENGTH_BYTES = 64;

        public function __construct(string $path) {
            // Delete existing socket file
            if (file_exists($path)) {
                unlink($path);
            }

            // Create and bind socket file
            $this->socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
            socket_bind($this->socket, $path);

            // Set socket file permissions
            chmod($path, ENV::get("socket_mode"));

            $this->client = null;
        }

        // Convert message from client into simple PHP-styled presentation of HTTP request
        private function request(string $request) {
            // Expecting ["<endpoint>","<method>","<payload>"]
            [$uri, $_SERVER["REQUEST_METHOD"], $data] = json_decode($request);

            // Request is malformed or connection interrupted, abort
            if (empty($uri)) {
                return false;
            }

            $uri = parse_url($uri);

            // Load request body fields into $_POST superglobal if set
            if (!empty($data)) {
                $_POST = json_decode($data, true);
            }

            $_SERVER['REQUEST_URI'] = isset($uri["path"]) ? "/" . $uri["path"] : "/"; // Set request path with leading slash
            isset($uri["query"]) ? parse_str($uri["query"], $_GET) : null; // Set request parameters
            
            // Initialize request router
            (new Router(Connection::AF_UNIX))->main();
        }

        // Transmit outgoing data over socket as stringifed JSON
        private function tx(\Socket &$client) {
            $payload = json_encode([$code, $msg]);
            $payload_size = strlen($payload);

            // Write string byte length prefix in header
            socket_write($client, $payload_size, self::HEADER_LENGTH_BYTES);
            // Write payload bytes
            socket_write($client, $payload, $payload_size);
        }

        // Receive data from socket and return as string
        private function rx(\Socket &$client): string {
            $length = (int) socket_read($client, self::HEADER_LENGTH_BYTES);
            return socket_read($client, $length);
        }

        private function txn() {
            // Create new connection for incoming request
            $client = socket_accept($this->socket);

            // Connect response transaction function
            ENV::set("SOCKET_STDOUT", fn&(mixed $msg, int $code = 200) => $this->tx($client));

            $this->request($this->rx($client));
        }

        // Stop server
        public function stop() {
            socket_close($this->socket);
        }

        // Start server
        public function listen() {
            socket_listen($this->socket);

            $con = true;
            $data = "";

            // Start new transaction on incoming request
            while ($con === true) {
                $this->txn();
            }

            socket_close($client);
        }
    }