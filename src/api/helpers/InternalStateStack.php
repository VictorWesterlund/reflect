<?php

    // Available superglobals
    enum Super: string {
        case GET    = "_GET";
        case ENV    = "_ENV";
        case POST   = "_POST";
        case SERVER = "_SERVER";
    }

    enum Flag {
        // This flag is used when a previously empty superglobal
        // is set. When restore() is called, the key value pair will
        // be removed completely. Normal primitive null can't be used
        // as it's a valid value and there would be no way to tell.
        case NULL;
    }

    // Set the value of a superglobal with a way to restore the initial
    // value when reqeusted.
    class InternalStateStack {
        public function __construct() {
            // Create store for each available superglobal
            foreach (Super::cases() as $super) {
                $this->{$super->value} = [];
            }
        }

        // Update a superglobal
        private static function set_global(string $super, string $key, mixed $value) {
            // Set superglobal value
            switch ($super) {
                case "_GET": $_GET[$key] = $value; break;
                case "_ENV": $_ENV[$key] = $value; break;
                case "_POST": $_POST[$key] = $value; break;
                case "_SERVER": $_SERVER[$key] = $value; break;
            }
        }

        // Store current superglobal 
        public function set(Super $super, string $key, mixed $value) {
            $super_values = &$GLOBALS[$super->value];
            
            // Store key value pairs if exist
            $this->{$super->value}[$key] = !empty($super_values[$key]) ? $super_values[$key] : Flag::NULL;

            // Update superglobal with new value
            return InternalStateStack::set_global($super->value, $key, $value);
        }

        // Restore superglobal values
        public function restore() {
            foreach ($this as $super => $stack) {
                foreach ($stack as $key => $value) {
                    // Unset superglobal key value pair
                    if ($value instanceof Flag) {
                        switch ($super) {
                            case "_GET": unset($_GET[$key]); break;
                            case "_ENV": unset($_ENV[$key]); break;
                            case "_POST": unset($_POST[$key]); break;
                            case "_SERVER": unset($_SERVER[$key]); break;
                        }
                        continue;
                    }

                    // Restore value for superglobal key
                    InternalStateStack::set_global($super, $key, $value);
                }

                // Remove stashed values
                $this->$super = null;
            }
        }
    }