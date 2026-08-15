<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Hostinger Manager
Description: Manage domains with expiry tracking and client/project linking.
Version: 1.0.1
Requires at least: 3.0.0
Author: Virrat global
Author URI: https://virratglobal.com/
*/

define('DOMAIN_MANAGER_MODULE_NAME', 'domain_manager_hostinger');
define('VERSION_DOMAIN_MANAGER', 100);
define('DOMAIN_MANAGER_MODULE_NAME_ITEM_ID', 'Domain Manager');

// Hooks
hooks()->add_action('admin_init', 'domain_manager_init_menu_items');
hooks()->add_action('admin_init', 'domain_manager_define_permissions');
hooks()->add_filter('module_domain_manager_hostinger_action_links', 'domain_manager_add_action_links');
hooks()->add_action('app_admin_head', 'domain_manager_add_head_components');
hooks()->add_action('after_cron_run', 'domain_manager_automated_sync');

/**
 * Automated sync via Perfex Cron.
 */
function domain_manager_automated_sync() {
    $last_run = get_option('domain_manager_last_cron_sync');
    // Run only once every 24 hours
    if (empty($last_run) || (time() - $last_run) > 86400) {
        $CI = &get_instance();
        $CI->load->model(DOMAIN_MANAGER_MODULE_NAME . '/hostinger_api_model');
        $CI->hostinger_api_model->sync_domains();
        $CI->hostinger_api_model->sync_websites();
        update_option('domain_manager_last_cron_sync', time());
    }
}

/**
 * Inject custom styles into admin head.
 */
function domain_manager_add_head_components() {
    if (get_instance()->app_modules->is_active(DOMAIN_MANAGER_MODULE_NAME)) {
        echo '<link href="' . module_dir_url(DOMAIN_MANAGER_MODULE_NAME, 'assets/css/style.css') . '" rel="stylesheet" type="text/css">';
    }
}


$CI = &get_instance();

// Register language files
register_language_files(DOMAIN_MANAGER_MODULE_NAME, [DOMAIN_MANAGER_MODULE_NAME]);

// Load helper functions
$CI->load->helper(DOMAIN_MANAGER_MODULE_NAME . '/domain_manager');

/**
 * Initialize Domain Manager module menu items.
 */
function domain_manager_init_menu_items()
{
    $CI = &get_instance();
    
    if (is_admin() || has_permission(DOMAIN_MANAGER_MODULE_NAME, '', 'view')) {
        $CI->app_menu->add_sidebar_menu_item(DOMAIN_MANAGER_MODULE_NAME, [
            'name'     => _l('hosting_manager'),
            'icon'     => 'fa fa-server',
            'href'     => '#',
            'position' => 30,
        ]);

        $CI->app_menu->add_sidebar_children_item(DOMAIN_MANAGER_MODULE_NAME, [
            'slug'     => 'domain_manager_list_item',
            'name'     => _l('domain_manager_domain_list'),
            'href'     => admin_url('domain_manager_hostinger'),
            'position' => 40,
        ]);
        if (is_admin() || has_permission(DOMAIN_MANAGER_MODULE_NAME, '', 'hosting_view')) {
            $CI->app_menu->add_sidebar_children_item(DOMAIN_MANAGER_MODULE_NAME, [
                'slug'     => 'hosting_view_list',
                'name'     => _l('domain_manager_websites_list'),
                'href'     => admin_url('domain_manager_hostinger/hosting_list'),
                'position' => 45,
            ]);
        }
        if (is_admin() || has_permission(DOMAIN_MANAGER_MODULE_NAME, '', 'hosting_view')) {
            $CI->app_menu->add_sidebar_children_item(DOMAIN_MANAGER_MODULE_NAME, [
                'slug'     => 'emails_view_list',
                'name'     => _l('domain_manager_emails_list'),
                'href'     => admin_url('domain_manager_hostinger/email_list'),
                'position' => 50,
            ]);
        }




    }

    if (is_admin() || has_permission(DOMAIN_MANAGER_MODULE_NAME, '', 'view')) {
        $CI->app_tabs->add_project_tab('domain_manager_projects', [
            'name'     => _l('domain_manager_domain_list'),
            'icon'     => 'fa fa-globe',
            'view'     => 'domain_manager_hostinger/admin/project_domain_manager',
            'position' => 10,
        ]);
        $CI->app_tabs->add_project_tab('domain_manager_websites', [
            'name'     => _l('domain_manager_websites_list'),
            'icon'     => 'fa fa-server',
            'view'     => 'domain_manager_hostinger/admin/project_hosting_manager',
            'position' => 11,
        ]);
        $CI->app_tabs->add_project_tab('domain_manager_emails', [
            'name'     => _l('domain_manager_emails_list'),
            'icon'     => 'fa fa-envelope',
            'view'     => 'domain_manager_hostinger/admin/project_emails_manager',
            'position' => 12,
        ]);
    }

    if (is_admin()) {
        $CI->app_menu->add_setup_menu_item(DOMAIN_MANAGER_MODULE_NAME, [
            'slug'     => 'domain_manager_setting',
            'name'     => _l('domain_manager_setting'),
            'href'     => admin_url('domain_manager_hostinger/setting'),
            'position' => 35,
        ]);
    }

    $CI->app_tabs->add_customer_profile_tab('domain_manager_domains', [
        'name'     => _l('domain_manager_domain_list'),
        'icon'     => 'fa fa-globe',
        'view'     => 'domain_manager_hostinger/admin/client_domain_manager',
        'position' => 10,
    ]);
    $CI->app_tabs->add_customer_profile_tab('domain_manager_websites', [
        'name'     => _l('domain_manager_websites_list'),
        'icon'     => 'fa fa-server',
        'view'     => 'domain_manager_hostinger/admin/client_hosting_manager',
        'position' => 11,
    ]);
    $CI->app_tabs->add_customer_profile_tab('domain_manager_emails', [
        'name'     => _l('domain_manager_emails_list'),
        'icon'     => 'fa fa-envelope',
        'view'     => 'domain_manager_hostinger/admin/client_emails_manager',
        'position' => 12,
    ]);
}

/**
 * Module activation hook.
 */
register_activation_hook(DOMAIN_MANAGER_MODULE_NAME, 'domain_manager_activate_module');

function domain_manager_activate_module()
{
    require_once __DIR__ . '/install.php';
}



/**
 * Define module permissions.
 */
function domain_manager_define_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view'   => _l('permission_view') . ' (' . _l('permission_global') . ')',
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
            'hosting_view'   => _l('hosting_permission_view') . ' (' . _l('permission_global') . ')',
            'hosting_create' => _l('hosting_permission_create'),
            'hosting_edit'   => _l('hosting_permission_edit'),
            'hosting_delete' => _l('hosting_permission_delete'),
            
        ],
    ];

    register_staff_capabilities(DOMAIN_MANAGER_MODULE_NAME, $capabilities, _l('domain_manager'));
}

/**
 * Add settings link in module list.
 *
 * @param array $actions Current actions.
 * @return array
 */
function domain_manager_add_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('domain_manager_hostinger/setting') . '">' . _l('settings') . '</a>';
    return $actions;
}
