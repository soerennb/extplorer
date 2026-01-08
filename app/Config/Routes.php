<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('login', 'Login::index');
$routes->post('login/auth', 'Login::auth');
$routes->get('logout', 'Login::logout');

$routes->group('api', function($routes) {
    $routes->get('ls', 'ApiController::ls');
    $routes->get('content', 'ApiController::content');
    $routes->post('save', 'ApiController::save');
    $routes->post('rm', 'ApiController::rm');
    $routes->post('mkdir', 'ApiController::mkdir');
    $routes->post('mv', 'ApiController::mv');
    $routes->post('cp', 'ApiController::cp');
    $routes->post('upload', 'ApiController::upload');
    $routes->get('download', 'ApiController::download');
    $routes->get('thumb', 'ApiController::thumb');
    $routes->post('archive', 'ApiController::archive');
    $routes->post('extract', 'ApiController::extract');
    $routes->post('chmod', 'ApiController::chmod');

    $routes->get('users', 'UserAdminController::index');
    $routes->post('users', 'UserAdminController::create');
    $routes->put('users/(:segment)', 'UserAdminController::update/$1');
    $routes->delete('users/(:segment)', 'UserAdminController::delete/$1');
    $routes->get('system', 'UserAdminController::systemInfo');

    $routes->put('profile/password', 'ProfileController::updatePassword');
});