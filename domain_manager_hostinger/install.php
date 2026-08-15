<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();



// Check and create 'domain_manager' table
if (!$CI->db->table_exists(db_prefix() . 'domain_manager')) {
    $sql_query = "CREATE TABLE IF NOT EXISTS `" . db_prefix() . "domain_manager` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `domain_name` VARCHAR(255) NOT NULL,
        `registrar` VARCHAR(255) DEFAULT NULL,
        `purchase_date` DATE DEFAULT NULL,
        `expiry_date` DATE DEFAULT NULL,
        `status` VARCHAR(255) NOT NULL DEFAULT 'active',
        `domain_type` VARCHAR(50) NOT NULL DEFAULT 'external',
        `dns_hosting` VARCHAR(255) NOT NULL DEFAULT 'enabled',
        `provider_name` VARCHAR(255) DEFAULT NULL,
        `provider_url` VARCHAR(255) DEFAULT NULL,
        `username` VARCHAR(255) DEFAULT NULL,
        `password` VARCHAR(255) DEFAULT NULL,
        `registration_status` VARCHAR(255) NOT NULL DEFAULT 'active',
        `client_id` INT(11) DEFAULT NULL,
        `project_id` INT(11) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `hostinger_domain_id` VARCHAR(100) DEFAULT NULL,
        `hostinger_synced_at` DATETIME DEFAULT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT '0',
        `created_at` DATETIME NULL DEFAULT NULL,
        `updated_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";";

    $CI->db->query($sql_query);
}

// Check and create 'hosting_details' table
if (!$CI->db->table_exists(db_prefix() . 'hosting_details')) {
    $sql_query = "CREATE TABLE IF NOT EXISTS `" . db_prefix() . "hosting_details` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `domain_id` INT(11) DEFAULT NULL,
        `website_name` VARCHAR(255) DEFAULT NULL,
        `provider` VARCHAR(255) DEFAULT NULL,
        `start_date` DATE DEFAULT NULL,
        `expiration_date` DATE DEFAULT NULL,
        `access_url` VARCHAR(255) DEFAULT NULL,
        `username` VARCHAR(255) DEFAULT NULL,
        `status` VARCHAR(255) NOT NULL DEFAULT 'active',
        `password` TEXT DEFAULT NULL,
        `client_id` INT(11) DEFAULT NULL,
        `project_id` INT(11) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `datacenter` VARCHAR(100) DEFAULT NULL,
        `hostinger_website_id` VARCHAR(100) DEFAULT NULL,
        `hostinger_synced_at` DATETIME DEFAULT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT '0',
        `created_at` DATETIME NULL DEFAULT NULL,
        `updated_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";";

    $CI->db->query($sql_query);
}

// Check and create 'emails_manager' table
if (!$CI->db->table_exists(db_prefix() . 'emails_manager')) {
    $sql_query = "CREATE TABLE IF NOT EXISTS `" . db_prefix() . "emails_manager` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `domain` VARCHAR(255) DEFAULT NULL,
        `mailbox_name` VARCHAR(255) NOT NULL,
        `available_count` INT(11) DEFAULT 0,
        `start_date` DATE DEFAULT NULL,
        `expiry_date` DATE DEFAULT NULL,
        `status` VARCHAR(255) NOT NULL DEFAULT 'active',
        `client_id` INT(11) DEFAULT NULL,
        `project_id` INT(11) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT '0',
        `created_at` DATETIME NULL DEFAULT NULL,
        `updated_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";";

    $CI->db->query($sql_query);
}

// Add columns to domain_manager for existing installs
if (!$CI->db->field_exists('domain_type', db_prefix() . 'domain_manager')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "domain_manager` ADD `domain_type` VARCHAR(50) NOT NULL DEFAULT 'external';");
}
if (!$CI->db->field_exists('hostinger_domain_id', db_prefix() . 'domain_manager')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "domain_manager` ADD `hostinger_domain_id` VARCHAR(100) NULL DEFAULT NULL;");
}
if (!$CI->db->field_exists('hostinger_synced_at', db_prefix() . 'domain_manager')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "domain_manager` ADD `hostinger_synced_at` DATETIME NULL DEFAULT NULL;");
}

// Add columns to hosting_details for existing installs
if (!$CI->db->field_exists('website_name', db_prefix() . 'hosting_details')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `website_name` VARCHAR(255) NULL DEFAULT NULL;");
}
if (!$CI->db->field_exists('datacenter', db_prefix() . 'hosting_details')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `datacenter` VARCHAR(100) NULL DEFAULT NULL;");
}
if (!$CI->db->field_exists('hostinger_website_id', db_prefix() . 'hosting_details')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `hostinger_website_id` VARCHAR(100) NULL DEFAULT NULL;");
}
if (!$CI->db->field_exists('hostinger_synced_at', db_prefix() . 'hosting_details')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `hostinger_synced_at` DATETIME NULL DEFAULT NULL;");
}

// Hostinger API token option
add_option('domain_manager_hostinger_api_token', '');

// Add columns to hosting_details for existing installs (access_url)
if (!$CI->db->field_exists('access_url', db_prefix() . 'hosting_details')) {
    $CI->db->query("ALTER TABLE `" . db_prefix() . "hosting_details` ADD `access_url` VARCHAR(255) NULL DEFAULT NULL;");
}

// Create emails_manager table for existing installs (if it doesn't exist)
if (!$CI->db->table_exists(db_prefix() . 'emails_manager')) {
    $sql_query = "CREATE TABLE IF NOT EXISTS `" . db_prefix() . "emails_manager` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `domain` VARCHAR(255) DEFAULT NULL,
        `mailbox_name` VARCHAR(255) NOT NULL,
        `available_count` INT(11) DEFAULT 0,
        `start_date` DATE DEFAULT NULL,
        `expiry_date` DATE DEFAULT NULL,
        `status` VARCHAR(255) NOT NULL DEFAULT 'active',
        `client_id` INT(11) DEFAULT NULL,
        `project_id` INT(11) DEFAULT NULL,
        `created_by` INT(11) DEFAULT NULL,
        `description` TEXT DEFAULT NULL,
        `deleted` TINYINT(1) NOT NULL DEFAULT '0',
        `created_at` DATETIME NULL DEFAULT NULL,
        `updated_at` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";";
    $CI->db->query($sql_query);
}
