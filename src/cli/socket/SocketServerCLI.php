<?php

    require_once Path::reflect("src/cli/CLI.php");
    require_once Path::reflect("src/cli/socket/SocketServer.php");

    class SocketServerCLI extends CLI {
        public function __construct(array $args) {
            parent::__construct($args, 1);

            // Send to handler function
            switch ($this->args[0]) {
                case "listen":
                    $this->listen();
                    break;

                default:
                    $this->error("Not a valid function");
                    break;
            }
        }

        private function listen() {
            if (empty($this->args[1])) {
                return $this->error("Expected path as next argument");
            }

            $this->echo("Reflect listening at '{$this->args[1]}' (Ctrl+C to stop)");

            // Try to initialize the socket server
            try {
                $this->server = new SocketServer($this->args[1]);
                $this->server->listen();
            } catch (Error $error) {
                $this->error("Failed to initialize socket server");
                return;
            }
        }
    }