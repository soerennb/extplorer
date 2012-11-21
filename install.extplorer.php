<?php
// ensure this file is being included by a parent file
if( !defined( '_JEXEC' ) && !defined( '_VALID_MOS' ) ) die( 'Restricted access' );
/**
* @version $Id$
* @package eXtplorer
* @copyright (C) 2005-2008 Soeren
* @license GNU / GPL
* @author soeren
* eXtplorer is Free Software
*/
function com_install($mypath =''){
	global $database;
	
	if( is_callable( array( 'JFactory', 'getDBO' ))) {
		$database = JFactory::getDBO();
	}
	if( $mypath == '' ) {
		$mypath = dirname(__FILE__);
	}
	require_once($mypath . "/include/functions.php");
	require_once($mypath . "/libraries/Archive/archive.php");
	
	ext_RaiseMemoryLimit( '50M' );
	error_reporting( E_ALL ^ E_NOTICE );
	
	$archive_name = $mypath.'/scripts.tar.gz';
	$extract_dir = $mypath.'/';
	
	$result = extArchive::extract( $archive_name, $extract_dir );
	if( !PEAR::isError( $result )) {
		unlink( $archive_name );
	} else {
		echo '<pre style="color:white; font-weight:bold; background-color:red;">Error!
		'.$result->getMessage().'
		</pre>';
	}
	if( !is_callable( array( $database, 'loadNextRow' ))) {

		$database->setQuery( "SELECT id FROM #__components WHERE admin_menu_link = 'option=com_extplorer'" );
		$id = $database->loadResult();

		//add new admin menu images
		$database->setQuery( "UPDATE #__components SET admin_menu_img = '../administrator/components/com_extplorer/images/x_icon.png', admin_menu_link = 'option=com_extplorer' WHERE id=$id");
		$database->query();
	}
}
class com_extplorerInstallerScript
{
 
        /**
         * Called on installation
         *
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function install( $adapter){
			$adminpath = JPATH_SITE.'/administrator/components/com_extplorer';
			com_install($adminpath);
			rename($adminpath.'/extplorer.j30.php', $adminpath.'/extplorer.php');
			return true;
		}
        /**
         * Called on update
         *
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         *
         * @return  boolean  True on success
         */
        public function update( $adapter) {
			return true;
		}
 
        /**
         * Called on uninstallation
         *
         * @param   JAdapterInstance  $adapter  The object responsible for running this script
         */
        public function uninstall( $adapter) {
			return true;
		}
}