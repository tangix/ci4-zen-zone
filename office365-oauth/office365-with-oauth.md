# Office 365 OAuth - Oh' what Joy!

Just completed a project migrating a support system relying on IMAP connectivity to Office 365 to use the now required OAuth 2.0 authentication. Previous version used simple username and password and the built-in `php_imap` library. Now with the migration to OAuth 2.0, PHP's ancient built-in IMAP library is no longer supported.

## OAuth 2.0 with Office 365

Following the auth-flow is pretty easy but there are many settings on the Azure AD-side that needs to be correct. Luckily the customer's admin manage that and I only need to get the PHP-side working. After some test the Azure Web (important!) Application was configured and we got the required `client_id`, `tenant`, `client_secret` and `redirect_url` properly configured. Noted that despite listing several `redirect_url`s in the interface, we could only get the most secure to work. So `http://localhost` was not longer valid when the `https://prod.customer.com/callback.php` redirect URL was entered - making testing harder. Companies with their own mail-domain need to make the Azure Application available to all users.

Instructions on how to [set up Office 365](https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth)

## The OAuth 2.0 flow

The basic idea to authenticate with OAuth is to give the user a specially crafted URL pointing to Microsoft's authentication server. After login, the user is redirected back to the originating server with a authentication `code` in the URL. The originating server will then use the short-lived `code` to request a `access_token` that is the token used when requesting information from Office 365. Together with the `access_token` a `refresh_token` is given that should be used when renewing the `access_token` before it expires (in 4000 seconds).

[Detailed instructions](https://learn.microsoft.com/en-us/azure/active-directory/develop/v2-oauth2-auth-code-flow)

The code to generate the URL is basically this (this is not a CodeIgniter project):

```php
function generate_office365_authurl($id = 0) {
    global $SQL, $global;

    // Get the email address of the account, to show login hint in Office 365
    $statement = 'SELECT address FROM mail WHERE mailID=' . $SQL->quote($id);
    $email = $SQL->result($statement, __LINE__, __FILE__);

	// $global holds the system settings
    $tenant_id = $global['office365_tenant'];

    // Generate verifier bytes and keep them a secret (will be used in latest step)
    $verifier_bytes = random_bytes(64);
    $code_verifier = rtrim(strtr(base64_encode($verifier_bytes), "+/", "-_"), "=");

    // Encode the verifier bytes 
    $challenge_bytes = hash("sha256", $code_verifier, true);
    $code_challenge = rtrim(strtr(base64_encode($challenge_bytes), "+/", "-_"), "=");

    $statement = 'UPDATE mail SET code_verifier=' . $SQL->quote($code_verifier) . ' ' .
        'WHERE mailID=' . $SQL->quote($id);
    $SQL->execute($statement, __LINE__, __FILE__);

    $data = [
        'client_id' => $global['office365_client_id'],
        'response_type' => 'code',
        'redirect_url' => $global['office365_redirect_url'],
        'response_mode' => 'query',
        'scope' => 'https://outlook.office.com/IMAP.AccessAsUser.All offline_access',
        'prompt' => 'login',
        'login_hint' => $email,
        'state' =>  'mailauth-' . $id,
        'code_challenge' => $code_challenge,
        'code_challenge_method' => 'S256'
    ];
    $url = 'https://login.microsoftonline.com/' . $tenant_id . '/oauth2/v2.0/authorize?';
    return $url . http_build_query($data);
}
```

Some things to note:

* the `scope` needs to include `offline_access` so the account is available for a server
* `code_challenge` and `code_challenge_method` relates to a security feature called [PKCE](https://tools.ietf.org/html/rfc7636) that secures the transaction, see below
* `login_hint` is a clever way to set the email address of the account to login to, so the user cannot make any mistakes
* `state` keeps track of the transaction. In our case this is the `id` of the mail account to validate

### Proof Key for Code Exchange (PKCE)

PKCE is a way to secure the token exchange between the client server and the authentication server. The idea is that the client server generates a secret `$verifier_bytes` that is stored in clear-text. A SHA256 hash of this secret `$verifier_bytes` is presented (`$code_challenge`) to the authentication server together with the login request. The authentication server stores this hash and returns the initial authorization `code` to `redirect_url`.

When requesting `access_token` from the authentication server, the clear-text `$verifier_bytes` is sent together with the application `client_secret` allowing the authentication server to validate the request by calculating the same SHA256 hash.

Some problems when using PHP is that the PKCE needs to be made `base64url`-safe according to [this standard](https://www.rfc-editor.org/rfc/rfc4648#section-5), normal `base64_encode()` will not work, that is why the construct `rtrim(strtr(base64_encode($s), "+/", "-_"), "=")` is required.

## Requesting the access tokens

When returned to the `redirect_url` callback, it is a straight-forward procedure to create a POST-request back to the authentication server. For this I use the [standard curl class](https://github.com/php-curl-class/php-curl-class).

```php
// Get the clear-text code_verifier
$statement = 'SELECT code_verifier FROM mail WHERE mailID=' . $SQL->quote($mailID);
$code_verifier = $SQL->result($statement, __LINE__, __FILE__);

// $global holds the system settings
$url = 'https://login.microsoftonline.com/' . $global['office365_tenant'] . '/oauth2/v2.0/token';

$data = [
    'client_id' => $global['office365_client_id'],
    'code' => $code,
    'redirect_url' => $global['office365_redirect_url'],
    'grant_type' => 'authorization_code',
    'client_secret' => $global['office365_client_secret'],
    'code_verifier' => $code_verifier
];

$curl = new \Curl\Curl();
$curl->post($url, $data);

if ($curl->error) {
    echo "<h2>Token Exchange with Office 365 Failed</h2>";
    echo $curl->errorMessage;
    var_dump($curl->response);
    exit;
}

$access_token = (string)$curl->response->access_token;
$refresh_token = (string)$curl->response->refresh_token;
```

With the `access_token` and `refresh_token` securely received we can now use the information to authenticate with the services we require from Office 365.

## Refreshing token

`access_token` is valid for about 4000 seconds and then a new token needs to be requested. For this a simple POST with the `refresh_token` should be sent to the authentication server. For example:

```php
function refresh_office365_token($id = 0) {
    global $SQL, $global;

    require "vendor/autoload.php";

    $statement = 'SELECT * FROM mail WHERE mailID=' . $SQL->quote($id);
    $res = $SQL->execute($statement, __LINE__, __FILE__);
    $ref = $SQL->assoc($res);

    // $global holds the system settings
    $url = 'https://login.microsoftonline.com/' . $global['office365_tenant'] . '/oauth2/v2.0/token';

    $data = [
        'client_id' => $global['office365_client_id'],
        'grant_type' => 'refresh_token',
        'client_secret' => $global['office365_client_secret'],
        'refresh_token' => $ref['refresh_token']
    ];

    $curl = new \Curl\Curl();
    $curl->post($url, $data);

    if ($curl->error) {
        echo "<h2>Token Exchange with Office 365 Failed</h2>";
        echo $curl->errorMessage;
        var_dump($curl->response);
        return false;
    }

    $access_token = (string)$curl->response->access_token;
    $refresh_token = (string)$curl->response->refresh_token;

    $statement = 'UPDATE mail SET ' .
        'access_token=' . $SQL->quote($access_token) . ',' .
        'refresh_token=' . $SQL->quote($refresh_token) . ',' .
        'token_expires=DATE_ADD(UTC_TIMESTAMP(), INTERVAL ' . (int)$curl->response->expires_in . ' SECOND)' .
        'WHERE mailID=' . $SQL->quote($id);

    $SQL->execute($statement);
    return true;
}
```

Strangely, the `refresh_token` seems to be valid forever.