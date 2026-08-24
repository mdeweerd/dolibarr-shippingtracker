<?php
/* Copyright (C) 2026 MDW <mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \defgroup   shippingtracker     Module ShippingTracker
 *  \brief      Module for tracking supplier orders and invoices
 *
 *  \file       core/modules/modShippingTracker.class.php
 *  \ingroup    shippingtracker
 *  \brief      Description and activation file for the module ShippingTracker
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module ShippingTracker
 */
class modShippingTracker extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 104300;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'shippingtracker';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "other";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Module label (no space allowed), used if translation string 'ModuleShippingTrackerName' not found
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModuleShippingTrackerDesc' not found
		$this->description = "ShippingTrackerDescription";
		$this->descriptionlong = "ShippingTrackerDescriptionLong";

		// Author
		$this->editor_name = 'MDW';
		$this->editor_url = 'https://github.com/mdeweerd/dolibarr-shippingtracker';

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0.0';

		// Key used in llx_const table to save module status enabled/disabled (where SHIPPINGTRACKER is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'generic';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 1,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 0,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set to relative path of css file if module has its own css file
			'css' => array(),
			// Set to relative path of js file if module must load a js on all pages
			'js' => array(),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code.
			'hooks' => array(
				'ordersuppliercard',
				'invoicesuppliercard'
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into shippingtracker/admin directory, to use to setup module.
		$this->config_page_url = array("shippingtracker_setup.php@shippingtracker");

		// Dependencies
		// List of module class names that must be enabled if this module is enabled
		$this->depends = array("modExpedition");
		$this->requiredby = array();
		$this->conflictwith = array();

		// The language file dedicated to your module
		$this->langfiles = array("shippingtracker@shippingtracker");

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		
		$r++;
		$this->rights[$r][0] = $this->numero . sprintf('%02d', 1);  // Permission ID: 10430001
		$this->rights[$r][1] = 'Manage shipping tracking information';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'manage_shipping_tracking';
		$this->rights[$r][5] = '';

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1, 'allentities')
		//             $this->const=array(2 => array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current')
		$this->const = array();

		// Array to add new tabs on some tabs
		$this->tabs = array();

		// Dictionnaries
		$this->dictionaries = array();

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains a class to show a box.
		$this->boxes = array();

		// Cronjobs
		// List of jobs to add when module is enabled
		$this->cronjobs = array();

		// add a cron job for the cleanup of the shared canvas
		// $this->cronjobs[0] = array(
		//     'label' => 'CleanUpSharedCanvas',
		//     'jobtype' => 'method',
		//     'class' => '/custom/shippingtracker/class/shippingtracker.class.php',
		//     'object' => 'ShippingTracker',
		//     'method' => 'cleanupSharedCanvas',
		//     'parameters' => '',
		//     'comment' => 'Remove old shared canvas files',
		//     'frequency' => array(
		//         'minute' => '*/30',
		//     ),
		//     'unitfrequency' => 3600,
		//     'status' => 1,
		//     'test' => '$conf->shippingtracker->enabled',
		//     'priority' => 50,
		// );

		// Permissions
		// $this->remove_perms = array(); // To remove permissions to users that was set in previous version (array('permkey' => int));
		// $this->remove_perms_object = array(); // To remove permissions to users that was set in previous version (array('permkey' => int));
		// $this->remove_perms_origin = array(); // To remove permissions to users that was set in previous version (array('permkey' => int));

		// $this->perms = array(); // To add permissions into a new group (array('groupname' => array('permkey' => int));

		// Main menus
		// Add here entries to declare new menus
		$this->menus = array();
		// Example to declare a new top menu entry:
		// $this->menus = array(array('mainmenu'=>'mymodule','leftmenu'=>'mymodule:myobject', 'type' => 'top', 'titre' => 'MyModuleTopMenu', 'mainmenu' => 'mymodule', 'leftmenu' => '', 'url' => '/mymodule/myobject_list.php', 'langs' => 'mylangs@mymodule', 'position' => 100, 'perms' => '$user->rights->mymodule->myobject->read', 'enabled' => 'isModEnabled("mymodule")', 'target' => '', 'user' => 2);
		// Example to declare a left menu entry into an existing top menu entry:
		// $this->menus = array(array('mainmenu' => 'mymodule', 'leftmenu' => 'mymodule:myobject', 'type' => 'left', 'titre' => 'MyObject', 'url' => '/mymodule/myobject_list.php', 'langs' => 'mylangs@mymodule', 'position' => 100, 'perms' => '$user->rights->mymodule->myobject->read', 'enabled' => 'isModEnabled("mymodule")', 'target' => '', 'user' => 2);
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function is called to create tables, add constants, add permissions and menus
	 *  into Dolibarr database.
	 *
	 *  @param      string  $options    Options when enabling module ('', 'newboxdefonly', 'nodata')
	 *  @return     int              1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		// Create extrafields when module is enabled
		require_once __DIR__ . '/../../lib/handlers/shipping_handler.php';
		ensure_tracking_extrafields('commande_fournisseur');
		ensure_tracking_extrafields('facture_fourn');

		// The permission to manage shipping tracking
		// This is already handled by the rights array in __construct

		return $this->_init(array(), $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string  $options    Options when disabling module ('', 'newboxdefonly')
	 *  @return     int              1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		return $this->_remove(array(), $options);
	}
}
