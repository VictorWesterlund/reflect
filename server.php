<?php

    if (php_sapi_name() !== "cli") {
        die("Must be run from command line");
    }

    require_once "src/Init.php";
    require_once Path::src("request/SocketServer.php");

    echo "\e[34m>\e[0m API Socket Server\n";
    echo "\e[34m>\e[0m Starting server..\e[91m ";
    $server = new SocketServer();
    echo "\e[0m\e[92mOK\e[0m\n";

    echo "\e[34m>\e[0m Listening at: '\e[36m{$_ENV["socket"]["listen"]}\e[0m'..\n";
    $server->listen();

