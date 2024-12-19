<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('phpinfo', 'Phpinfo::index');
$routes->get('database', 'Database::index');
