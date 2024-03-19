<?php

	namespace Reflect;

	// Allowed HTTP verbs
    enum Method: string {
        case GET     = "GET";
        case POST    = "POST";
        case PUT     = "PUT";
        case DELETE  = "DELETE";
        case PATCH   = "PATCH";
        case OPTIONS = "OPTIONS";
    }