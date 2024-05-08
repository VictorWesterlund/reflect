<?php

	namespace Reflect\Database\Models\Endpoints;

	enum EndpointsModel: string {		
		const TABLE = "endpoints";

		case ID     = "id";
		case ACTIVE = "active";
	}