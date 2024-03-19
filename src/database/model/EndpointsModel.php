<?php

	namespace Reflect\Database\Model\Endpoints;

	enum EndpointsModel: string {		
		const TABLE = "endpoints";

		case ID     = "id";
		case ACTIVE = "active";
	}