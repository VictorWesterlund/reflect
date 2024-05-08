<?php

	namespace Reflect;

	use victorwesterlund\GlobalSnapshot;

	use Reflect\ENV;
	use Reflect\Path;
	use Reflect\Method;
	use Reflect\Response;
	use Reflect\Request\Router;
	use Reflect\Request\Connection;

	require_once Path::reflect("src/request/Router.php");
	require_once Path::reflect("src/reflect/Method.php");
	require_once Path::reflect("src/reflect/Response.php");

	class Call {
		private Method $method;
		private readonly GlobalSnapshot $snapshot;
		
		protected string $endpoint;
		protected array $params = [];

		public function __construct(string $endpoint) {
			$endpoint = explode("?", $endpoint, 2);

			// Set search parameters from endpoint string if provided
			if (count($endpoint) == 2) {
				// Spanws $params variable
				parse_str($endpoint[1], $params);

				$this->params($params);
			}

			$this->endpoint = $endpoint[0];
			// Remove leading slash if present
			if (substr($this->endpoint, 0, 1) === "/") {
				$this->endpoint = substr($this->endpoint, 1, strlen($this->endpoint) - 1);
			}

			$this->snapshot = new GlobalSnapshot();
		}

		// Truncate GET superglobal and repopulate it with search params
		private function set_superglobal_params_proxy(): void {
			$_GET = [];

			foreach ($this->params as $key => $value) {
				$_GET[$key] = $value;
			}
		}

		// Truncate POST superglobal and repopulate it with values from method call
		private function set_superglobal_body_proxy(array $payload): void {
			$_POST = [];
				
			foreach ($payload as $key => $value) {
				$_POST[$key] = $value;
			}
		}

		// Proxy new request and dispatch to Reflect request Router
		private function dispatch(array $payload = []): Response {
			// Cache all current superglobals
			$this->snapshot->capture();

			// Set request method
			$_SERVER["REQUEST_METHOD"] = $this->method->value; 
			// Set requested endpoint path with leading slash
			$_SERVER["REQUEST_URI"] = "/" . $this->endpoint;

			$this->set_superglobal_params_proxy();
			$this->set_superglobal_body_proxy($payload);

			// Set Content-Type request header if a payload has been provided
			if (!empty($payload)) {
				$_SERVER["HTTP_CONTENT_TYPE"] = "application/json";
			}

			// Set flag to let stdout() know that we wish to return instead of exit.
			ENV::set(ENV::INTERNAL_STDOUT, true);

			// Start proxied request Router
			$response = (new Router(Connection::INTERNAL))->main();

			// Restore cached superglobals
			$this->snapshot->restore();

			return $response;
		}

		// ----

		public function params(array $params = []): self {
			$this->params = array_merge($this->params, $params);
			return $this;
		}

		// ----

		// Make a GET request to endpoint with optional search parameters
		public function get(): Response {
			$this->method = Method::GET;
			return $this->dispatch();
		}

		public function patch(?array $payload = []): Response {
			$this->method = Method::PATCH;
			return $this->dispatch($payload);
		}

		public function put(?array $payload = []): Response {
			$this->method = Method::PUT;
			return $this->dispatch($payload);
		}

		public function post(?array $payload = []): Response {
			$this->method = Method::POST;
			return $this->dispatch($payload);
		}

		public function delete(?array $payload = []): Response {
			$this->method = Method::DELETE;
			return $this->dispatch($payload);
		}
	}
