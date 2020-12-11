<?php

/**
 * @created      2020-12-11
 * @author       Mattias Sandström <msa@tangix.com>
 * @copyright    2020 Mattias Sandström
 * @license      MIT
 */


namespace App\Controllers;


use CodeIgniter\Controller;

class Options extends Controller
{

    public function index()
    {
        return $this->response->setHeader('Access-Control-Allow-Methods', 'DELETE, POST, GET, PUT, OPTIONS')
            ->setHeader(
                'Access-Control-Allow-Headers',
                'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With'
            )
            ->setHeader('Access-Control-Allow-Origin', 'https://<front-end-spa-url>')
            ->setHeader('Access-Control-Max-Age', '3600')
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setStatusCode(204);
    }

}
