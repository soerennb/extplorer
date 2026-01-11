<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('login', 'Login::index');
$routes->post('login/auth', 'Login::auth');
$routes->get('logout', 'Login::logout');

$routes->add('dav', 'DavController::index');
$routes->add('dav/(:any)', 'DavController::index/$1');

$routes->group('api', function($routes) {
    $routes->get('ls', 'ApiController::ls');
    $routes->get('content', 'ApiController::content');
    $routes->post('save', 'ApiController::save');
    $routes->post('rm', 'ApiController::rm');
    $routes->post('mkdir', 'ApiController::mkdir');
    $routes->post('mv', 'ApiController::mv');
    $routes->post('cp', 'ApiController::cp');
    $routes->post('upload', 'ApiController::upload');
    $routes->post('upload_chunk', 'ApiController::uploadChunk');
    $routes->get('download', 'ApiController::download');
    $routes->get('thumb', 'ApiController::thumb');
    $routes->get('search', 'ApiController::search');
    $routes->get('dirsize', 'ApiController::dirsize');
    $routes->post('archive', 'ApiController::archive');
    $routes->post('extract', 'ApiController::extract');
    $routes->post('chmod', 'ApiController::chmod');
    $routes->post('chown', 'ApiController::chown');

    // Trash
    $routes->get('trash/list', 'ApiController::trashList');
    $routes->post('trash/restore', 'ApiController::trashRestore');
    $routes->post('trash/delete', 'ApiController::trashDelete');
    $routes->post('trash/empty', 'ApiController::trashEmpty');

    $routes->get('users', 'UserAdminController::index');
    $routes->post('users', 'UserAdminController::create');
    $routes->put('users/(:segment)', 'UserAdminController::update/$1');
    $routes->delete('users/(:segment)', 'UserAdminController::delete/$1');
    
    $routes->get('roles', 'UserAdminController::getRoles');
    $routes->post('roles', 'UserAdminController::saveRole');
    $routes->delete('roles/(:segment)', 'UserAdminController::deleteRole/$1');

    $routes->get('groups', 'UserAdminController::getGroups');
    $routes->post('groups', 'UserAdminController::saveGroup');
    $routes->delete('groups/(:segment)', 'UserAdminController::deleteGroup/$1');

    $routes->get('system', 'UserAdminController::systemInfo');
    $routes->get('logs', 'UserAdminController::getLogs');

    // Profile
    $routes->get('profile/details', 'ProfileController::getDetails');
    $routes->put('profile/password', 'ProfileController::updatePassword');
    $routes->get('profile/2fa/setup', 'ProfileController::setup2fa');
    $routes->post('profile/2fa/enable', 'ProfileController::enable2fa');
    $routes->post('profile/2fa/disable', 'ProfileController::disable2fa');
});