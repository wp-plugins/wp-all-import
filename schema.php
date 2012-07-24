<?php
/**
 * Plugin database schema
 * WARNING: 
 * 	dbDelta() doesn't like empty lines in schema string, so don't put them there;
 *  WPDB doesn't like NULL values so better not to have them in the tables;
 */

/**
 * The database character collate.
 * @var string
 * @global string
 * @name $charset_collate
 */
$charset_collate = '';

// Declare these as global in case schema.php is included from a function.
global $wpdb, $plugin_queries;

if ( ! empty($wpdb->charset))
	$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
if ( ! empty($wpdb->collate))
	$charset_collate .= " COLLATE $wpdb->collate";
	
$table_prefix = PMXI_Plugin::getInstance()->getTablePrefix();

$plugin_queries = <<<SCHEMA
CREATE TABLE {$table_prefix}templates (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	options TEXT,
	scheduled VARCHAR(64) NOT NULL DEFAULT '',
	name VARCHAR(200) NOT NULL DEFAULT '',
	title TEXT,
	content LONGTEXT,
	is_keep_linebreaks TINYINT(1) NOT NULL DEFAULT 0,
	PRIMARY KEY  (id)
) $charset_collate;
CREATE TABLE {$table_prefix}imports (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(255) NOT NULL DEFAULT '',
	type VARCHAR(32) NOT NULL DEFAULT '',
	path TEXT,
	xpath VARCHAR(255) NOT NULL DEFAULT '',
	template LONGTEXT,
	options TEXT,
	scheduled VARCHAR(64) NOT NULL DEFAULT '',
	registered_on DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY  (id)
) $charset_collate;
CREATE TABLE {$table_prefix}posts (
	post_id BIGINT(20) UNSIGNED NOT NULL,
	import_id BIGINT(20) UNSIGNED NOT NULL,
	unique_key TEXT,
	PRIMARY KEY  (post_id)
) $charset_collate;
CREATE TABLE {$table_prefix}files (
	id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	import_id BIGINT(20) UNSIGNED NOT NULL,
	name VARCHAR(255) NOT NULL DEFAULT '',
	path TEXT,
	registered_on DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
	PRIMARY KEY  (id)
) $charset_collate;
SCHEMA;
