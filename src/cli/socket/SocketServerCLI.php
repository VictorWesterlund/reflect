<?php

    namespace Reflect\CLI;

    use \Reflect\Path;
    use \Reflect\CLI\CLI;
    use const \Reflect\ENV;
    use \Reflect\Socket\SocketServer;

    require_once Path::reflect("src/cli/CLI.php");
    require_once Path::reflect("src/cli/socket/SocketServer.php");

    class SocketServerCLI extends CLI {
        public function __construct(array $args) {
            parent::__construct($args, 1);

            // Send to handler function
            switch ($this->args[0]) {
                case "listen":
                    $this->start();
                    break;

                default:
                    $this->error("Not a valid function");
                    break;
            }
        }

        private function start() {
            if (empty($_ENV[ENV]["socket"])) {
                return $this->error("No socket path", "'socket' variable in '.env.ini' must be set to an absolute path on disk");
            }

            $this->echo("Starting server...");

            // Try to initialize the socket server
            try {
                $this->server = new SocketServer($_ENV[ENV]["socket"]);

                $this->echo("Listening at '\e[1m\e[95m{$_ENV[ENV]["socket"]}\e[0m' \e[37m(Ctrl+C to stop)\e[0m");

                $this->server->listen();
            } catch (\Exception $error) {
                $this->error($error->getMessage());
                $this->restart();
            }
        }

        public function stop() {
            $this->echo("Stopping server...");
            $this->server->stop();
        }

        public function restart() {
            $this->stop();
            $this->start();
        }
    }