# Code for Bearer Authorization Filter

This code is not complete and does not run without all other dependencies and classes. I consider this a pseudo-code and it is thus put here as a code-block and not as a source file.

```php
<?php

/**
 * @created      2020-12-11
 * @author       Mattias Sandström <msa@tangix.com>
 * @copyright    2020 Mattias Sandström
 * @license      MIT
 */

namespace App\Filters;


use App\Classes\ApiResponse;
use App\Libraries\AuthBearer;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;

class BearerAuthFilter implements FilterInterface
{

    /**
     * Generate a RFC6750 compliant response
     *
     * @param string $error
     * @param string $error_description
     * @param int    $status
     *
     * @return Response
     */
    private function failAuthorization(
        string $error = 'unknown',
        $error_description = 'Unknown error',
        int $status = 401
    ): Response {
        $response = Services::response();
        return $response->setStatusCode($status)
            ->setHeader('WWW-Authenticate',
                'Bearer error="' . $error . '" error_description="' . $error_description . '"');
    }

    /**
     * Generate a RFC6750 compliant response when user has not access to a resource
     *
     * @param string $section
     *
     * @return Response
     */
    private function failAccess(string $section = ''): Response
    {
        $response = Services::response();
        return $response->setStatusCode(403)
            ->setHeader('WWW-Authenticate',
                'Bearer scope="' . $section . '" error="insufficient_scope" error_description="No access to resource"');
    }

    /**
     * @inheritDoc
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {

    }

    /**
     * @inheritDoc
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $bearer = Services::bearer();
        $result = new ApiResponse(false);

        if ($request->getMethod(false) === 'options') {
            return $request;
        }

        // Check the Authorization Header from the Bearer and the GET request if it is missing
        // Bail out if the Bearer string is not validated

        $access_token = '';
        $authorization = $request->getHeader('Authorization');

        if (is_null($authorization)) {
            $authorization = $request->getGetPost('access_token');
            if (is_null($authorization)) {
                return $this->failAuthorization('invalid_request', 'Authorization token missing', 400);
            }
            $access_token = 'Bearer ' . $authorization;
        }
        else {
            $access_token = $authorization->getValue();
        }

        if (is_null($authorization)) {
            return $this->failAuthorization('invalid_request', 'Authorization header missing', 400);
        }

        if (strtolower(substr($access_token, 0, 7)) !== 'bearer ') {
            return $this->failAuthorization('invalid_request', 'Bearer token missing in request', 400);
        }

        try {
            // Remove the string "Bearer " from the Authorization header and check the token
            if ( ! $bearer->validate(substr($access_token, 7))) {
                return $this->failAuthorization('invalid_token', 'The token cannot be validated');
            }

            // Token is valid. Now check if there are any limits on the route.
            // ---------------------------------------------------------------
            if (in_array('conf', $arguments)) {
                if ($bearer->getSection(AuthBearer::SECTION_CONF) === 0x0) {
                    return $this->failAccess(AuthBearer::SECTION_CONF);
                }
            }
            if (in_array('comp', $arguments)) {
                if ($bearer->getSection(AuthBearer::SECTION_COMP) === 0x0) {
                    return $this->failAccess(AuthBearer::SECTION_COMP);
                }
            }

            // The Authorization was validated and valid. Let us continue.
            return $request;
        } catch (ExpiredException $e) {
            $result->setMessage('TOKEN_EXPIRED');
        } catch (BeforeValidException $e) {
            $result->setMessage('TOKEN_NOT_VALID_YET');
        } catch (\Exception $e) {
            $result->setMessage($e->getMessage());
        }

        return $this->failAuthorization('invalid_token', $result->getMessage());
    }
}
```
