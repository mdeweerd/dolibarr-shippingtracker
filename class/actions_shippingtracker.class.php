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
 * \file    class/actions_shippingtracker.class.php
 * \ingroup shippingtracker
 * \brief   Hooks for ShippingTracker module
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonhookactions.class.php';

require_once __DIR__ . '/../lib/handlers/shipping_handler.php';

/**
 * Class for hooks of ShippingTracker module
 */
class ActionsShippingTracker extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Execute actions
	 *
	 * @param  array           $parameters Hook parameters
	 * @param  CommonObject    $object     The object to process
	 * @param  string          $action     Current action
	 * @param  HookManager     $hookmanager Hook manager
	 * @return int                          0 or 1
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user, $conf;

		if (!isModEnabled('shippingtracker')) {
			return 0;
		}

		// Check if user has permission to manage shipping tracking
		if (!isset($user->rights->shippingtracker) || empty($user->rights->shippingtracker->manage_shipping_tracking)) {
			return 0;
		}

		// Load language file
		$langs->loadLangs(array('shippingtracker@shippingtracker'));

		// Handle set tracking action
		if ($action == 'set_tracking') {
			return $this->handleSetTracking($object, $action);
		}

		// Handle edit tracking action
		if ($action == 'edit_tracking') {
			return $this->handleEditTracking($object, $action);
		}

		return 0;
	}

	/**
	 * Overloading the formObjectOptions function : replacing the parent's function
	 *
	 * @param  array           $parameters Hook parameters
	 * @param  CommonObject    $object     The object to process
	 * @param  string          $action     Current action
	 * @param  HookManager     $hookmanager Hook manager
	 * @return int                          0 or 1
	 */
	public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user, $conf;

		// Load language file
		$langs->loadLangs(array('shippingtracker@shippingtracker'));

		if (!isModEnabled('shippingtracker')) {
			return 0;
		}

		// Only for supplier orders and supplier invoices
		$valid_types = array('commande_fournisseur', 'facture_fourn');
		if (!in_array($object->table_element, $valid_types)) {
			return 0;
		}

		// Initialize action if not set
		if (!isset($action)) {
			$action = '';
		}

		// Display tracking UI
		$this->displayTrackingUI($object, $action);

		return 0;
	}

	/**
	 * Display the tracking UI section
	 *
	 * @param CommonObject $object The object to display tracking for
	 * @param string $action Current action
	 * @return void
	 */
	private function displayTrackingUI(&$object, $action = '')
	{
		global $langs, $user, $conf;

		// Check if user has permission
		$can_edit = !empty($user->rights->shippingtracker->manage_shipping_tracking);

		// Get current tracking info from extrafields
		$tracking_info = $this->getTrackingInfoFromExtrafields($object);

		// Get available shipping methods
		$carriers = get_available_shipping_methods($object);

		// Display tracking info - follow Dolibarr pattern with nested table in first td
		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tbody><tr>';
		print '<td class="nowrap">'.$langs->trans('ShippingTracking').'</td>';
		print '<td></td>';
		if ($can_edit && $action != 'edit_tracking') {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit_tracking&token='.newToken().'" title="'.$langs->trans('Edit').'"><span class="fas fa-pencil-alt" style="color: #444; float: right"></span></a></td>';
		} else {
			print '<td></td>';
		}
		print '</tr></tbody></table>';
		print '</td><td>';

		if ($action == 'edit_tracking') {
			// Edit mode - show form fields
			print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="set_tracking">';
			print '<table class="noborder">';
			print '<tr><td>'.$langs->trans('TrackingNumber').'</td><td>';
			print '<input type="text" name="tracking_awb" id="tracking_awb" value="'.dol_escape_htmltag($tracking_info['awb']).'" class="flat">';
			print '</td></tr>';
			print '<tr><td>'.$langs->trans('SendingMethod').'</td><td>';
			print '<select name="carrier_code" id="carrier_code" class="flat minwidth200">';
			print '<option value="">-- '.$langs->trans('Select').' --</option>';
			foreach ($carriers as $code => $carrier) {
				$selected = ($code == $tracking_info['carrier_code']) ? ' selected' : '';
				print '<option value="'.dol_escape_htmltag($code).'"'.$selected.'>'.dol_escape_htmltag($carrier['name']).'</option>';
			}
			print '</select>';
			print '</td></tr>';
			$tracking_link_value = '';
			if (!empty($tracking_info['awb']) && !empty($tracking_info['carrier_code'])) {
				$generated_link = generate_tracking_link($tracking_info['awb'], $tracking_info['carrier_code']);
				if ($tracking_info['tracking_link'] === $generated_link) {
					$tracking_link_value = '';
				} else {
					$tracking_link_value = $tracking_info['tracking_link'];
				}
			} else if (!empty($tracking_info['tracking_link'])) {
				$tracking_link_value = $tracking_info['tracking_link'];
			}
			print '<tr><td>'.$langs->trans('TrackingLink').'</td><td>';
			print '<input type="text" name="tracking_link" id="tracking_link" value="'.dol_escape_htmltag($tracking_link_value).'" class="flat" placeholder="https://...">';
			print '</td></tr>';
			print '</table>';
			print '<div class="center" style="margin-top: 10px;">';
			print '<input type="submit" class="button" name="save_tracking" value="'.$langs->trans('Save').'">';
			print ' &nbsp; ';
			print '<input type="submit" class="button" name="clear_tracking" value="'.$langs->trans('Remove').'" onclick="return confirmClear();">';
			print ' &nbsp; ';
			print '<input type="button" class="button" name="cancel_tracking" value="'.$langs->trans('Cancel').'" onclick="return confirmCancel();">';
			print '</div>';
			print '</form>';
		} else {
			// View mode - show tracking info as single line
			$display_text = '';

			// Build AWB part
			if (!empty($tracking_info['awb'])) {
				$display_text = dol_escape_htmltag($tracking_info['awb']);

				// Add carrier if available
				if (!empty($tracking_info['carrier_code']) && isset($carriers[$tracking_info['carrier_code']])) {
					$display_text .= ' - ' . dol_escape_htmltag($carriers[$tracking_info['carrier_code']]['name']);
				}

				// Make AWB a link if tracking_link exists
				if (!empty($tracking_info['tracking_link'])) {
					$display_text = '<a href="'.dol_escape_htmltag($tracking_info['tracking_link']).'" target="_blank"><span class="fas fa-truck paddingright"></span>'.$display_text.'</a>';
				}
			} else {
				$display_text = '';
			}
			print $display_text;
		}
		print '</td></tr>';

		// Add JavaScript for edit functionality
		$confirmCancelMsg = $langs->trans('ConfirmCancelTrackingEdit');
		$confirmClearMsg = $langs->trans('ConfirmClearTrackingInfo');
		print '<script type="text/javascript">';
		print 'function startEditTracking() {';
		print '    document.location.href = "'.dol_escape_js($_SERVER['PHP_SELF']).'?id='.$object->id.'&action=edit_tracking&token='.newToken().'";';
		print '}';
		print 'function confirmCancel() {';
		print '    if (confirm("'.dol_escape_js($confirmCancelMsg).'")) {';
		print '        document.location.href = "'.dol_escape_js($_SERVER['PHP_SELF']).'?id='.$object->id.'&token='.newToken().'";';
		print '        return true;';
		print '    }';
		print '    return false;';
		print '}';
		print 'function confirmClear() {';
		print '    if (confirm("'.dol_escape_js($confirmClearMsg).'")) {';
		print '        return true;';
		print '    }';
		print '    return false;';
		print '}';
		print '</script>';
	}

	/**
	 * Handle setting tracking information
	 *
	 * @param CommonObject $object The object to set tracking for
	 * @param string $action The current action
	 * @return int 0 or 1
	 */
	private function handleSetTracking(&$object, &$action)
	{
		global $langs, $user;

		// Check if cancel was clicked
		if (GETPOST('cancel_tracking', 'alpha')) {
			$action = '';
			return 1;
		}

		// Check if clear was clicked
		if (GETPOST('clear_tracking', 'alpha')) {
			// Clear all tracking information
			$result = $this->saveTrackingInfo($object, '', '', '');
			if ($result > 0) {
				setEventMessages($langs->trans('TrackingInfoCleared'), array(), 'mesgs');
				$action = '';
			} else {
				setEventMessages($langs->trans('ErrorFailedToClearTrackingInfo'), array(), 'errors');
				$action = 'edit_tracking';
			}
			return 1;
		}

		// Check if save was clicked
		if (GETPOST('save_tracking', 'alpha')) {
			$tracking_awb = GETPOST('tracking_awb', 'alpha');
			$carrier_code = GETPOST('carrier_code', 'alpha');
			$tracking_link = GETPOST('tracking_link', 'alpha');

			// Allow empty fields to clear tracking info
			// Save tracking information
			$result = $this->saveTrackingInfo($object, $tracking_awb, $carrier_code, $tracking_link);

			if ($result > 0) {
				// Sync tracking to linked invoices if this is a supplier order
				if ($object->table_element == 'commande_fournisseur') {
					$sync_result = $this->syncTrackingToInvoices($object);
					if ($sync_result < 0) {
						setEventMessages($langs->trans('ErrorSyncTrackingToInvoices'), array(), 'warnings');
					} else {
						setEventMessages($langs->trans('TrackingInfoSavedAndSynced'), array(), 'mesgs');
					}
				} else {
					setEventMessages($langs->trans('TrackingInfoSaved'), array(), 'mesgs');
				}
				$action = '';
			} else {
				setEventMessages($langs->trans('ErrorFailedToSetTrackingInfo'), array(), 'errors');
				$action = 'edit_tracking';
			}

			return 1;
		}

		return 0;
	}

	/**
	 * Handle edit tracking action
	 *
	 * @param CommonObject $object The object to edit tracking for
	 * @param string $action The current action
	 * @return int 0 or 1
	 */
	private function handleEditTracking(&$object, &$action)
	{
		// Just display the edit form, actual handling is in displayTrackingUI
		return 0;
	}

	/**
	 * Save tracking information to object
	 *
	 * @param CommonObject $object The object to save tracking for
	 * @param string $awb Tracking number
	 * @param string $carrier_code Carrier code
	 * @param string $tracking_link Tracking link (optional)
	 * @return int >0 if OK, <=0 if KO
	 */
	private function saveTrackingInfo(&$object, $awb, $carrier_code, $tracking_link = '')
	{
		global $user;

		// Ensure extrafields are defined
		ensure_tracking_extrafields($object->table_element);

		// Set the tracking data in array_options (extrafields)
		$object->array_options['options_tracking_awb'] = $awb;
		$object->array_options['options_carrier_code'] = $carrier_code;

		// Generate tracking link if not provided
		if (empty($tracking_link)) {
			$object->array_options['options_tracking_link'] = generate_tracking_link($awb, $carrier_code);
		} else {
			$object->array_options['options_tracking_link'] = $tracking_link;
		}

		// Update the object in database
		$result = $object->update($user);

		if ($result > 0) {
			return 1;
		}

		return -1;
	}

	/**
	 * Get tracking information from extrafields
	 *
	 * @param CommonObject $object The object to get tracking info from
	 * @return array Tracking info (awb, carrier_code, tracking_link)
	 */
	private function getTrackingInfoFromExtrafields($object)
	{
		$tracking_info = array(
			'awb' => '',
			'carrier_code' => '',
			'tracking_link' => ''
		);

		// Load extrafields if not already loaded
		if (!isset($object->array_options) || !is_array($object->array_options)) {
			$object->fetch_optionals();
		}

		if (isset($object->array_options['options_tracking_awb'])) {
			$tracking_info['awb'] = $object->array_options['options_tracking_awb'];
			// Handle empty/zero values
			if ($tracking_info['awb'] === '0' || $tracking_info['awb'] === 0 || empty($tracking_info['awb'])) {
				$tracking_info['awb'] = '';
			}
		}
		if (isset($object->array_options['options_carrier_code'])) {
			$tracking_info['carrier_code'] = $object->array_options['options_carrier_code'];
			if ($tracking_info['carrier_code'] === '0' || $tracking_info['carrier_code'] === 0 || empty($tracking_info['carrier_code'])) {
				$tracking_info['carrier_code'] = '';
			}
		}
		if (isset($object->array_options['options_tracking_link'])) {
			$tracking_info['tracking_link'] = $object->array_options['options_tracking_link'];
			if (empty($tracking_info['tracking_link'])) {
				$tracking_info['tracking_link'] = '';
			}
		}

		return $tracking_info;
	}

	/**
	 * Sync tracking information from supplier order to linked supplier invoices
	 *
	 * @param CommonObject $order The supplier order object
	 * @return int >0 if OK, <0 if error
	 */
	private function syncTrackingToInvoices($order)
	{
		global $db, $langs;

		// Include necessary classes
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

		// Get tracking info from order
		$tracking_info = $this->getTrackingInfoFromExtrafields($order);

		if (empty($tracking_info['awb']) || empty($tracking_info['carrier_code'])) {
			return 0; // No tracking info to sync
		}

		// Find linked invoices
		$order->fetchObjectLinked('', '', null, '', 'OR', 1);
		$invoices = isset($order->linkedObjects['facture_fourn']) ? $order->linkedObjects['facture_fourn'] : array();

		if (!is_array($invoices) || empty($invoices)) {
			return 0; // No linked invoices
		}

		$updated = 0;
		$warnings = 0;

		foreach ($invoices as $invoice) {
			// Skip if invoice is not valid
			if ($invoice->status != FactureFournisseur::STATUS_VALIDATED) {
				continue;
			}

			// Get current tracking info for invoice
			$invoice_tracking = $this->getTrackingInfoFromExtrafields($invoice);

			// Check if invoice has different tracking
			if (!empty($invoice_tracking['awb']) && $invoice_tracking['awb'] != $tracking_info['awb']) {
				$warnings++;
				continue;
			}

			// Update invoice with order's tracking info
			$invoice->array_options['options_tracking_awb'] = $tracking_info['awb'];
			$invoice->array_options['options_carrier_code'] = $tracking_info['carrier_code'];
			$invoice->array_options['options_tracking_link'] = $tracking_info['tracking_link'];

			$invoice->update($user);
			$updated++;
		}

		// If there were warnings, set a message
		if ($warnings > 0) {
			setEventMessages($langs->trans('InvoiceHasDifferentTracking'), array(), 'warnings');
		}

		return $updated;
	}
}
