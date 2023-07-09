<?php

    namespace Reflect\Helpers;

    // Capture the current state of all superglobals.
    // This will save a copy of all keys and values and any changes made to the superglobals 
    // can be restored to this point in time by calling $this->restore();
    class GlobalSnapshot {
        public function __construct() {
            foreach (array_keys($GLOBALS) as $global) {
                $this->{$global} = $GLOBALS[$global];
            }
        }

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
    }