<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Phpinfo extends BaseController
{
    public function index() {
        if (ENVIRONMENT !== 'production') {
            phpinfo();
        }
        else {
            return $this->response->setBody('Not enabled');
        }
    }

}
