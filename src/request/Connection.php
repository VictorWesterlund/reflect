<?php

	namespace Reflect\Request;

	// Client/server connection medium
	enum Connection {
		case AF_UNIX;
		case HTTP;
		case INTERNAL;
	}