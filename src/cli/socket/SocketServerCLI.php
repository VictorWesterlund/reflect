<?php

    namespace Reflect\CLI;

    use \Reflect\ENV;
    use \Reflect\Path;
    use \Reflect\CLI\CLI;
    use \Reflect\Socket\SocketServer;

    require_once Path::reflect("src/cli/CLI.php");
    require_once Path::reflect("src/cli/socket/SocketServer.php");

    class SocketServerCLI extends CLI {
        public function __construct(array $args) {
            parent::__construct($args, 1);

            // Path to socket file
            $this->socket = ENV::get("socket");

            // Send to handler function
            switch ($this->args[0]) {
                case "listen":
                    $this->start();
                    break;

                default:
                    $this->error("Not a valid function", "expected 'socket listen'");
                    break;
            }
        }

        private function start() {
            $socket = ENV::get("socket");

            if (!$this->socket) {
                return $this->error("No socket path", "'socket' variable in '.env.ini' must be set to an absolute path on disk");
            }

            // User running this script does not have +rw access to the folder specified in the config
            if (!is_writable(dirname($this->socket)))    {
                return $this->error(
                    "Can not open socket file at location",
                    "Read and write permission for user '" . posix_getpwuid(posix_geteuid())['name'] . "' to folder '" . dirname($this->socket) . "'"
                );
            }

            $this->echo("Starting Reflect socket server...");

            // Try to initialize the socket server
            try {
                $this->server = new SocketServer($this->socket);

                $this->echo("Listening at '\e[1m\e[95m{$this->socket}\e[0m' \e[37m(Ctrl+C to stop)\e[0m");

                $this->server->listen();
            } catch (\Exception $error) {
                $this->error($error->getMessage());
                $this->restart();
            }
        }

        public function stop() {
            $this->echo("Stopping Reflect socket server...");

            if ($this->server instanceof SocketServer) {
                $this->server->stop();
            }
        }

        public function restart() {
            $this->stop();
            $this->start();
        }
    }