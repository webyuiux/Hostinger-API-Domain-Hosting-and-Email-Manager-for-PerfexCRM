<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // --- domain_manager table additions ---
        $dm = db_prefix() . 'domain_manager';

        if (!$CI->db->field_exists('hostinger_domain_id', $dm)) {
            $CI->db->query("ALTER TABLE `{$dm}` ADD `hostinger_domain_id` VARCHAR(255) DEFAULT NULL AFTER `description`;");
        }
        if (!$CI->db->field_exists('domain_type', $dm)) {
            $CI->db->query("ALTER TABLE `{$dm}` ADD `domain_type` VARCHAR(50) DEFAULT 'external' AFTER `hostinger_domain_id`;");
        }
        if (!$CI->db->field_exists('hostinger_synced_at', $dm)) {
            $CI->db->query("ALTER TABLE `{$dm}` ADD `hostinger_synced_at` DATETIME DEFAULT NULL AFTER `domain_type`;");
        }

        // --- hosting_details table additions ---
        $hd = db_prefix() . 'hosting_details';

        if (!$CI->db->field_exists('hostinger_website_id', $hd)) {
            $CI->db->query("ALTER TABLE `{$hd}` ADD `hostinger_website_id` VARCHAR(255) DEFAULT NULL AFTER `description`;");
        }
        if (!$CI->db->field_exists('website_name', $hd)) {
            $CI->db->query("ALTER TABLE `{$hd}` ADD `website_name` VARCHAR(255) DEFAULT NULL AFTER `hostinger_website_id`;");
        }
        if (!$CI->db->field_exists('datacenter', $hd)) {
            $CI->db->query("ALTER TABLE `{$hd}` ADD `datacenter` VARCHAR(100) DEFAULT NULL AFTER `website_name`;");
        }
        if (!$CI->db->field_exists('hostinger_synced_at', $hd)) {
            $CI->db->query("ALTER TABLE `{$hd}` ADD `hostinger_synced_at` DATETIME DEFAULT NULL AFTER `datacenter`;");
        }

        // Save option placeholder for API token
        add_option('domain_manager_hostinger_api_token', '');
    }
}
