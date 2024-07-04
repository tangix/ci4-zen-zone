# CURLRequest and HTTP/2 problems

Wasted many hours troubleshooting a CodeIgniter 4.5.3 project deployed in Google Cloud Run. The project is deployed as a docker container, built on the local development machine and uploaded to the Artifact Registry.

The project uses an external API for certain functions. I've wrapped the API calls into its own class to manage authentication and provide simple calls from the rest of my code.

Using CodeIgniter's [`CURLRequest`](https://codeigniter4.github.io/userguide/libraries/curlrequest.html#curlrequest-class) class as a wrapper I have calls like this:

```php
try {
	$response = $curl->request(
		'POST',
		'https://<api>',
		[
			'form_params' => [
				'grant_type' => 'client_credentials',
			],
			'auth' => $auth_config,
		]
	);

	$auth = json_decode($response->getBody());
	
	// Process response
	
} catch (Exception $e) {
	// Handle error
}
```

## "It works here"

Developing and running locally having zero issues calling the API. However, when deploying to Google Cloud Run, the same calls from the container running the same project returned this cryptic error:

```
92: HTTP/2 stream 1 was not closed cleanly: PROTOCOL_ERROR (err 1)
```

Hmmm, googling around and finding surprisingly little about this error. Some references back to `curl` which implemented HTTP/2 as default protocol (with HTTP/1.1 fallback) many versions ago.

Throwing caution to the wind, I asked Chat GPT for a possible solution and was rewarded with this piece of code using `curl` natively:

```php
	// ...

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $api_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0); // Ensure HTTP/2 is used if supported
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Authorization: Bearer ' . $api_key,
		'Accept: */*',
		'Content-Type: application/json',
		'Content-Length: ' . strlen($data),
	]);
	curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Set an appropriate timeout
	curl_setopt($ch, CURLOPT_VERBOSE, true); // Enable verbose output for debugging
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Verify SSL certificate
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); // Verify SSL hostname
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);


	$response = curl_exec($ch);
```

Lo and behold - **this code worked!** 

## Enabling HTTP/2 in CURLRequest should be easy, right?

Comparing with my own code using `CURLRequest` and I spot the difference easily - *and it's related to HTTP/2:*

```php
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
```

Reading the CI documentation on the [`version`](https://codeigniter4.github.io/userguide/libraries/curlrequest.html#version) configuration *I passed '2.0' as* `version`, simply because I don't really like working with floats:

```php
$response = $curl->request(
	'POST',
	'https://<api>',
	[
		'form_params' => [
			'grant_type' => 'client_credentials',
		],
		'auth' => $auth_config,
		'version' => '2.0',
	]
);
```

Still no go so I started digging deeper.

## "Use the source, Luke!"

After lots of debugging and tracing the calls through `CodeIgniter\HTTP\CURLRequest` I spotted the error in the library's `setCURLOptions()` function:

```php
// version
if (! empty($config['version'])) {
	if ($config['version'] === 1.0) {
		$curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_0;
	} elseif ($config['version'] === 1.1) {
		$curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
	} elseif ($config['version'] === 2.0) {
		$curlOptions[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
	}
}
```

The `===` comparison! So, the docs are **wrong** - `version` must be float!

In this case, I would have appreciated an Exception being thrown when I passed `version` as a string, but the CodeIgniter framework doesn't work that way consistently. Ssome `ConfigException` are thrown, but mainly from configurations in `app/Config` files as I have understood it.

## Solution

Changed my call to use a float `2.0` instead and all started working magically when deployed to Google Cloud Run. Why it works when setting the version is still a mystery, but since the service is hosted behind a `HTTP/2` enabled gateway, the guess is some incompatibilities due to protocol downgrade (as suggested by some Google docs discussing the use of VPC, NAT and outbound API Gateways).

Submitted a [PR](https://github.com/codeigniter4/CodeIgniter4/pull/9021) to allow strings as value for `version`.