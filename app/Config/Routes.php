<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('admin', 'Home::admin');
$routes->get('health', 'Health::index');
$routes->get('login', 'Login::index');
$routes->post('login/auth', 'Login::auth');
$routes->post('login/test-remote', 'Login::testRemote');
$routes->post('logout', 'Login::logout');

$routes->get('install', 'Install::index');
$routes->post('install/create', 'Install::createAdmin');

$routes->add('dav', 'DavController::index');
$routes->add('dav/(:any)', 'DavController::index/$1');

// Public Shares
$routes->get('s/(:segment)', 'ShareController::index/$1');
$routes->post('s/(:segment)/auth', 'ShareController::auth/$1');
$routes->get('s/(:segment)/download', 'ShareController::download/$1');
$routes->get('s/(:segment)/ls', 'ShareController::ls/$1');
$routes->post('s/(:segment)/upload', 'ShareController::upload/$1');

$routes->group('api', function($routes) {
    $routes->get('ls', 'ApiFileController::ls');
    $routes->get('content', 'ApiFileController::content');
    $routes->post('save', 'ApiFileController::save');
    $routes->post('rm', 'ApiFileController::rm');
    $routes->post('mkdir', 'ApiFileController::mkdir');
    $routes->post('mv', 'ApiFileController::mv');
    $routes->post('cp', 'ApiFileController::cp');
    $routes->post('upload', 'ApiTransferController::upload');
    $routes->post('upload_chunk', 'ApiTransferController::uploadChunk');
    $routes->post('upload/session', 'ApiTransferController::uploadSessionCreate');
    $routes->put('upload/session/(:segment)/chunk/(:num)', 'ApiTransferController::uploadSessionChunk/$1/$2');
    $routes->post('upload/session/(:segment)/complete', 'ApiTransferController::uploadSessionComplete/$1');
    $routes->delete('upload/session/(:segment)', 'ApiTransferController::uploadSessionAbort/$1');
    $routes->get('download', 'ApiDownloadController::download');
    $routes->get('thumb', 'ApiDownloadController::thumb');
    $routes->get('search', 'ApiDownloadController::search');
    $routes->get('dirsize', 'ApiDownloadController::dirsize');
    $routes->post('archive', 'ApiDownloadController::archive');
    $routes->post('extract', 'ApiDownloadController::extract');
    $routes->post('chmod', 'ApiDownloadController::chmod');
    $routes->post('chown', 'ApiFileController::chown');

    // Share Management
    $routes->post('share/create', 'ApiShareController::shareCreate');
    $routes->post('share/delete', 'ApiShareController::shareDelete');
    $routes->get('share/list', 'ApiShareController::shareList');
    $routes->get('share/policy', 'ApiShareController::sharePolicy');

    // Trash
    $routes->get('trash/list', 'ApiFileController::trashList');
    $routes->post('trash/restore', 'ApiFileController::trashRestore');
    $routes->post('trash/delete', 'ApiFileController::trashDelete');
    $routes->post('trash/empty', 'ApiFileController::trashEmpty');

    // Versions
    $routes->get('versions/list', 'ApiFileController::versionList');
    $routes->post('versions/restore', 'ApiFileController::versionRestore');

    // Mounts
    $routes->get('mounts', 'MountController::index');
    $routes->get('mounts/(:segment)', 'MountController::show/$1');
    $routes->post('mounts', 'MountController::create');
    $routes->post('mounts/test', 'MountController::test');
    $routes->put('mounts/(:segment)', 'MountController::update/$1');
    $routes->delete('mounts/(:segment)', 'MountController::delete/$1');

    $routes->get('users', 'UserAdminController::index');
    $routes->post('users', 'UserAdminController::create');
    $routes->put('users/(:segment)', 'UserAdminController::update/$1');
    $routes->delete('users/(:segment)', 'UserAdminController::delete/$1');
    $routes->get('users/(:segment)/permissions', 'UserAdminController::userPermissions/$1');
    
    $routes->get('roles', 'UserAdminController::getRoles');
    $routes->post('roles', 'UserAdminController::saveRole');
    $routes->get('roles/(:segment)/usage', 'UserAdminController::roleUsage/$1');
    $routes->delete('roles/(:segment)', 'UserAdminController::deleteRole/$1');

    $routes->get('groups', 'UserAdminController::getGroups');
    $routes->post('groups', 'UserAdminController::saveGroup');
    $routes->get('groups/(:segment)/usage', 'UserAdminController::groupUsage/$1');
    $routes->delete('groups/(:segment)', 'UserAdminController::deleteGroup/$1');

    $routes->get('permissions/catalog', 'UserAdminController::permissionsCatalog');

    $routes->get('settings', 'SettingsController::index');
    $routes->post('settings', 'SettingsController::update');
    $routes->post('settings/test-email', 'SettingsController::testEmail');
    $routes->post('settings/validate-email', 'SettingsController::validateEmail');

    $routes->get('system', 'UserAdminController::systemInfo');
    $routes->get('logs', 'UserAdminController::getLogs');
    $routes->get('logs/query', 'UserAdminController::queryLogs');

    // Transfer
    $routes->get('transfer/status', 'TransferController::status');
    $routes->post('transfer/stage', 'TransferController::stage');
    $routes->post('transfer/upload', 'TransferController::upload');
    $routes->post('transfer/send', 'TransferController::send');
    $routes->get('transfer/history', 'TransferController::history');
    $routes->delete('transfer/(:segment)', 'TransferController::delete/$1');

    // Profile
    $routes->get('profile/details', 'ProfileController::getDetails');
    $routes->put('profile/password', 'ProfileController::updatePassword');
    $routes->get('profile/2fa/setup', 'ProfileController::setup2fa');
    $routes->post('profile/2fa/enable', 'ProfileController::enable2fa');
    $routes->post('profile/2fa/disable', 'ProfileController::disable2fa');
});
