<?php

    namespace Reflect\Helpers;

    // Capture the current state of all superglobals.
    // This will save a copy of all keys and values and any changes made to the superglobals 
    // can be restored to this point in time by calling $this->restore();
    class GlobalSnapshot {
        // Declare properties for PHP superglobals
        private array $_ENV;
        private array $_GET;
        private array $_POST;
        private array $_FILES;
        private array $_SERVER;
        private array $_COOKIE;
        private array $_REQUEST;
        private array $_SESSION;

        private int $argc;
        private array $argv;
        private ?array $__composer_autoload_files;

        public function __construct() {}

        // Clear a superglobal array
        private function truncate(string $global) {
            global $$global;
            $$global = [];
        }

        // Restore state of superglobals at the time of capture
        public function restore() {
            foreach ($this as $global => $values) {
                global $$global;

                $this->truncate($global);
                $$global = $this->{$global};
            }
        }

        public function capture() {
            foreach (array_keys($GLOBALS) as $global) {
                $this->{$global} = $GLOBALS[$global];
            }
        }
    }