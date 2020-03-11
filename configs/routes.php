<?php
/**
 * Routes Configuration
 *
 * This files stores all the routes for the core WHSuite system.
 *
 * @package  WHSuite-Configs
 * @author  WHSuite Dev Team <info@whsuite.com>
 * @copyright  Copyright (c) 2013, Turn 24 Ltd.
 * @license http://whsuite.com/license/ The WHSuite License Agreement
 * @link http://whsuite.com
 * @since  Version 1.0
 */

/**
 * Admin Routes
 */
App::get('router')->attach('/admin', array(
    'name_prefix' => 'admin-',
    'values' => array(
        'sub-folder' => 'admin',
        'addon' => 'cpanel'
    ),
    'params' => array(
        'id' => '(\d+)'
    ),

    'routes' => array(
        'service-cpanel-manage' => array(
            'params' => array(
                'service_id' => '(\d+)',
            ),
            'path' => '/client/profile/{:id}/service/{:service_id}/cpanel/hosting/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'manageHosting'
            )
        ),
        'service-cpanel-create' => array(
            'params' => array(
                'service_id' => '(\d+)',
            ),
            'path' => '/client/profile/{:id}/service/{:service_id}/cpanel/hosting/create/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'createAccount'
            )
        ),
        'service-cpanel-suspend' => array(
            'params' => array(
                'service_id' => '(\d+)',
            ),
            'path' => '/client/profile/{:id}/service/{:service_id}/cpanel/hosting/suspend/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'suspendAccount'
            )
        ),
        'service-cpanel-unsuspend' => array(
            'params' => array(
                'service_id' => '(\d+)',
            ),
            'path' => '/client/profile/{:id}/service/{:service_id}/cpanel/hosting/unsuspend/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'unsuspendAccount'
            )
        ),
        'service-cpanel-terminate' => array(
            'params' => array(
                'service_id' => '(\d+)',
            ),
            'path' => '/client/profile/{:id}/service/{:service_id}/cpanel/hosting/terminate/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'terminateAccount'
            )
        ),
        'server-cpanel-manage' => array(
            'params' => array(
                'server_id' => '(\d+)',
            ),
            'path' => '/servers/group/{:id}/server/{:server_id}/cpanel/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'manageServer'
            )
        ),
        'server-cpanel-reboot' => array(
            'params' => array(
                'server_id' => '(\d+)',
            ),
            'path' => '/servers/group/{:id}/server/{:server_id}/cpanel/reboot/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'rebootServer'
            )
        ),
        'server-cpanel-restart-service' => array(
            'params' => array(
                'server_id' => '(\d+)',
                'service' => '(\w+)'
            ),
            'path' => '/servers/group/{:id}/server/{:server_id}/cpanel/restart/{:service}/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'restartService'
            )
        ),
    )
));


/**
 * Client Routes
 */

App::get('router')->attach('', array(
    'name_prefix' => 'client-',
    'values' => array(
        'sub-folder' => 'client',
        'addon' => 'cpanel'
    ),
    'params' => array(
        'id' => '(\d+)'
    ),

    'routes' => array(
        'service-cpanel-manage' => array(
            'path' => '/cpanel/manage/{:id}/',
            'values' => array(
                'controller' => 'CpanelController',
                'action' => 'manageHosting'
            )
        ),
    ),
));
