<?php

	namespace Reflect\Request;

	// Allowed connection media
	enum Connection {
		case HTTP;
		case INTERNAL;
	}