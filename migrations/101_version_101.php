<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {

        $CI = &get_instance();
        
        if (!$CI->db->table_exists(db_prefix() . 'domain_manager')) {

            $sql_query = "CREATE TABLE IF NOT EXISTS `" . db_prefix() . "domain_manager` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `domain_name` VARCHAR(255) NOT NULL,  -- Domain name
                `registrar` VARCHAR(255) DEFAULT NULL,
                `purchase_date` DATE DEFAULT NULL,
                `expiry_date` DATE DEFAULT NULL,
                `status` VARCHAR(255) NOT NULL DEFAULT 'active',
                `dns_hosting` VARCHAR(255) NOT NULL DEFAULT 'enabled',
                `provider_name` VARCHAR(255) DEFAULT NULL,
                `provider_url` VARCHAR(255) DEFAULT NULL,
                `username` VARCHAR(255) DEFAULT NULL,
                `password` VARCHAR(255) DEFAULT NULL,
                `registration_status` VARCHAR(255) NOT NULL DEFAULT 'active',
                `client_id` INT(11) DEFAULT NULL,  -- Links to clients table
                `project_id` INT(11) DEFAULT NULL, -- Links to projects table
                `created_by` INT(11) DEFAULT NULL, -- Links to projects table
                `description` TEXT DEFAULT NULL,  -- Additional notes
                `deleted` TINYINT(1) NOT NULL DEFAULT '0', 
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            );";


            $CI->db->query($sql_query);
            
        }

        if (!$CI->db->table_exists(db_prefix() . 'hosting_details')) {

            $sql_query = "CREATE TABLE IF NOT EXISTS `" . db_prefix() . "hosting_details` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `domain_id` INT  DEFAULT NULL,
                `provider` VARCHAR(255)  DEFAULT NULL,
                `start_date` DATE  DEFAULT NULL,
                `expiration_date` DATE  DEFAULT NULL,
                `access_url` VARCHAR(255)  DEFAULT NULL,
                `username` VARCHAR(255) DEFAULT NULL,
                `status` VARCHAR(255) NOT NULL DEFAULT 'active',
                `password` TEXT  DEFAULT NULL,
                `client_id` INT(11) DEFAULT NULL,  -- Links to clients table
                `project_id` INT(11) DEFAULT NULL, -- Links to projects table
                `created_by` INT(11) DEFAULT NULL, -- Links to projects table
                `description` TEXT DEFAULT NULL,  -- Additional notes
                `deleted` TINYINT(1) NOT NULL DEFAULT '0', 
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            );";

            $CI->db->query($sql_query);
            
        }


        

        $domain_manager = db_prefix() . 'domain_manager';
        if (!$CI->db->field_exists('provider_name', $domain_manager)) {
            $CI->db->query("ALTER TABLE `" . $domain_manager . "` ADD `provider_name` VARCHAR(255) DEFAULT NULL  AFTER `dns_hosting`;");
        }
        if (!$CI->db->field_exists('provider_url', $domain_manager)) {
            $CI->db->query("ALTER TABLE `" . $domain_manager . "` ADD `provider_url` VARCHAR(255) DEFAULT NULL  AFTER `dns_hosting`;");
        }
        if (!$CI->db->field_exists('username', $domain_manager)) {
            $CI->db->query("ALTER TABLE `" . $domain_manager . "` ADD `username` VARCHAR(255) DEFAULT NULL  AFTER `dns_hosting`;");
        }
        if (!$CI->db->field_exists('password', $domain_manager)) {
            $CI->db->query("ALTER TABLE `" . $domain_manager . "` ADD `password` VARCHAR(255) DEFAULT NULL  AFTER `dns_hosting`;");
        }

    }
}