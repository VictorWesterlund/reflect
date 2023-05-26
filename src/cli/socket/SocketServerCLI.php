<?php

    namespace Reflect\CLI;

    use \Reflect\Path;
    use \Reflect\CLI\CLI;
    use \Reflect\Socket\SocketServer;

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

            $this->echo("Starting server...");

            // Try to initialize the socket server
            try {
                $this->server = new SocketServer($this->args[1]);

                $this->echo("Listening at '\e[1m\e[95m{$this->args[1]}\e[0m' \e[37m(Ctrl+C to stop)\e[0m");

                $this->server->listen();
            } catch (\Error $error) {
                $this->error("Socket: {$error::getMessage()}");
                return;
            }
        }
    }