<?php
/*
 * Plugin Name: RW BP Group Hierarchy
 * Plugin URI: https://github.com/rpi-virtuell/rw-bp-group-hierarchy
 * Description: Allows BuddyPress groups to belong to other groups. Forked from BP Group Hierarchy from David Dean (http://www.generalthreat.com/projects/buddypress-group-hierarchy/)
 * Version: 1.5.5
 * Tested up to: WP 4.6.1, BuddyPress 2.7
 * License: Example: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
 * Author: Frank Staude
 * Author URI: https://www.staude.net/
 * Contributer: Joachim Happel http://joachim-happel.de
 * GitHub Plugin URI: https://github.com/rpi-virtuell/rw-bp-group-hierarchy
 * GitHub Branch:     master
 * Requires WP:       4.6
 * Requires BP:       2.7
 * Requires PHP:      5.3
*/

define ( 'BP_GROUP_HIERARCHY_IS_INSTALLED', 1 );
define ( 'BP_GROUP_HIERARCHY_VERSION', '1.5.4' );
define ( 'BP_GROUP_HIERARCHY_DB_VERSION', 1 );
if( ! defined( 'BP_GROUP_HIERARCHY_SLUG' ) )
	define ( 'BP_GROUP_HIERARCHY_SLUG', 'hierarchy' );

require ( dirname( __FILE__ ) . '/bp-group-hierarchy-filters.php' );
require ( dirname( __FILE__ ) . '/bp-group-hierarchy-actions.php' );
require ( dirname( __FILE__ ) . '/bp-group-hierarchy-widgets.php' );

/*************************************************************************
*********************SETUP AND INSTALLATION*******************************
*************************************************************************/

register_activation_hook( __FILE__, 'bp_group_hierarchy_install' );
/**
 * Install and/or upgrade the database
 */
function bp_group_hierarchy_install() {
	global $wpdb, $bp;
	// Check whether BP is active and whether Groups component is loaded, and throw error if not
	if( ! ( function_exists( 'buddypress' ) || is_a( $bp, 'BuddyPress' ) ) || ! bp_is_active( 'groups' ) ) {
		_e( 'BuddyPress is not installed or the Groups component is not activated. Cannot continue install.', 'bp-group-hierarchy' );
		exit;
	}

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
	
	$sql[] = "CREATE TABLE {$bp->groups->table_name} (
				parent_id BIGINT(20) NOT NULL DEFAULT 0,
				KEY parent_id (parent_id),
			) {$charset_collate};
	 	   ";

	if( ! get_site_option( 'bp-group-hierarchy-db-version' ) || get_site_option( 'bp-group-hierarchy-db-version' ) < BP_GROUP_HIERARCHY_DB_VERSION || ! bp_group_hierarchy_verify_install() ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
	}
	
	if( bp_group_hierarchy_verify_install( true ) ) {
		update_site_option( 'bp-group-hierarchy-db-version', BP_GROUP_HIERARCHY_DB_VERSION );
	} else {
		die('Could not create the required column.  Please enable debugging for more details.');
	}
}

/**
 * Try to DESCRIBE the groups table to see whether the column exists / was added
 * @param bool $debug_column Whether to report that the required column wasn't found - this is normal pre-install
 */
function bp_group_hierarchy_verify_install( $debug_column = false ) {

	global $wpdb, $bp;

	/** Manually confirm that parent_id column exists */
	$parent_id_exists = true;
	$columns = $wpdb->get_results( 'DESCRIBE ' . $bp->groups->table_name );
	
	if( $columns ) {
		$parent_id_exists = false;
		foreach( $columns as $column ) {
			if( $column->Field == 'parent_id') {
				$parent_id_exists = true;
				break;
			}
		}
		
		if( ! $parent_id_exists && $debug_column ) {
			bp_group_hierarchy_debug( 'Required column was not found - last MySQL error was: ' . $wpdb->last_error );
			return $parent_id_exists;
		}
		
	} else {
		bp_group_hierarchy_debug( 'Could not DESCRIBE table - last MySQL error was: ' . $wpdb->last_error );
		return false;
	}
	
	return $parent_id_exists;
	
}

/**
 * Debugging function
 */
function bp_group_hierarchy_debug( $message ) {

	if( ! defined( 'WP_DEBUG') || ! WP_DEBUG )	return;

	if( is_array( $message ) || is_object( $message ) ) {
		$message = print_r( $message, true );
	}

	if(defined( 'WP_DEBUG_LOG') && WP_DEBUG_LOG ) {
		$GLOBALS['wp_log']['bp_group_hierarchy'][] = 'BP Group Hierarchy - ' .  $message;
		error_log('BP Group Hierarchy - ' .  $message);
	}

	if( defined('WP_DEBUG_DISPLAY') && false !== WP_DEBUG_DISPLAY) {
		echo '<div class="log">BP Group Hierarchy - ' . $message . "</div>\n";
	}
	
}

?>
