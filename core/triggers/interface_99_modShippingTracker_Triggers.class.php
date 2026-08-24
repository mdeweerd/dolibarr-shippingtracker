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
 * \file    core/triggers/interface_99_modShippingTracker_Triggers.class.php
 * \ingroup shippingtracker
 * \brief   Triggers for ShippingTracker module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once __DIR__ . '/../../lib/handlers/shipping_handler.php';

/**
 * Class of triggers for ShippingTracker module
 */
class InterfaceShippingTrackerTriggers extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		parent::__construct($db);
		$this->family = "shippingtracker";
		$this->description = "ShippingTracker triggers for syncing tracking information.";
		$this->version = '1.0.0';
		$this->picto = 'generic';
	}

	/**
	 * ShippingTracker trigger run function
	 *
	 * @param string     $action Event action code
	 * @param CommonObject $object Object
	 * @param User       $user   Object user
	 * @param Translate  $langs  Object langs
	 * @param Conf       $conf  Object conf
	 * @return int              Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('shippingtracker')) {
			return 0;
		}

		$error = 0;

		dol_syslog("Trigger '" . $this->name . "' for action '" . $action . "' launched by " . __FILE__ . ". id=" . $object->id);

		// SUPPLIER ORDER MODIFY - Sync tracking to linked invoices
		if ($action == 'SUPPLIER_ORDER_MODIFY' && $object->table_element == 'commande_fournisseur') {
			$error += $this->syncTrackingOnOrderModify($object, $user, $langs);
		}

		// SUPPLIER INVOICE MODIFY - Check if tracking changed and warn if different from order
		if ($action == 'SUPPLIER_INVOICE_MODIFY' && $object->table_element == 'facture_fourn') {
			$error += $this->checkInvoiceTrackingConsistency($object, $user, $langs);
		}

		return $error ? -1 : 1;
	}

	/**
	 * Sync tracking information to linked invoices when order is modified
	 *
	 * @param CommonObject $order The supplier order object
	 * @param User $user The user who triggered the action
	 * @param Translate $langs Language object
	 * @return int 0 if OK, >0 if errors
	 */
	private function syncTrackingOnOrderModify(&$order, User $user, Translate $langs)
	{
		global $db;

		// Check if tracking info was changed in the order
		$order->fetch_optionals();

		$has_tracking = !empty($order->array_options['options_tracking_awb']);
		
		if (!$has_tracking) {
			return 0; // No tracking info to sync
		}

		// Include necessary classes
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

		// Find linked invoices
		$facture_fournisseur = new FactureFournisseur($db);
		
		// Try to get linked invoices - use the order's getLinkedInvoices method if available
		// Otherwise, try to find invoices that reference this order
		$invoices = array();
		
		if (method_exists($order, 'getLinkedInvoices')) {
			$invoices = $order->getLinkedInvoices();
		} else {
			// Manual query to find linked invoices
			$sql = "SELECT f.rowid, f.ref";
			$sql .= " FROM " . $db->prefix() . "facture_fourn as f";
			$sql .= " WHERE f.fk_commande = " . (int) $order->id;
			
			$resql = $db->query($sql);
			if ($resql) {
				while ($obj = $db->fetch_object($resql)) {
					$invoice = new FactureFournisseur($db);
					$invoice->fetch($obj->rowid);
					$invoices[] = $invoice;
				}
				$db->free($resql);
			}
		}

		if (empty($invoices)) {
			return 0; // No linked invoices
		}

		$updated = 0;
		
		foreach ($invoices as $invoice) {
			// Skip if invoice is not valid
			if ($invoice->status != FactureFournisseur::STATUS_VALIDATED) {
				continue;
			}

			$invoice->fetch_optionals();

			// Check if invoice has different tracking
			$invoice_has_tracking = !empty($invoice->array_options['options_tracking_awb']);
			$order_awb = $order->array_options['options_tracking_awb'];
			$invoice_awb = $invoice->array_options['options_tracking_awb'];

			if ($invoice_has_tracking && $invoice_awb != $order_awb) {
				// Don't overwrite existing tracking on invoice
				continue;
			}

			// Update invoice with order's tracking info
			$invoice->array_options['options_tracking_awb'] = $order->array_options['options_tracking_awb'];
			$invoice->array_options['options_carrier_code'] = $order->array_options['options_carrier_code'];
			$invoice->array_options['options_tracking_link'] = $order->array_options['options_tracking_link'];

			$invoice->update($user);
			$updated++;
		}

		return 0; // No errors
	}

	/**
	 * Check if invoice tracking is consistent with its linked order
	 *
	 * @param CommonObject $invoice The supplier invoice object
	 * @param User $user The user who triggered the action
	 * @param Translate $langs Language object
	 * @return int 0 if OK, >0 if warnings
	 */
	private function checkInvoiceTrackingConsistency(&$invoice, User $user, Translate $langs)
	{
		global $db;

		// Check if invoice has tracking info
		$invoice->fetch_optionals();
		
		$invoice_has_tracking = !empty($invoice->array_options['options_tracking_awb']);
		
		if (!$invoice_has_tracking) {
			return 0; // No tracking on invoice, nothing to check
		}

		// Find linked order
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
		
		$order = null;
		if (isset($invoice->fk_commande) && $invoice->fk_commande > 0) {
			$order = new CommandeFournisseur($db);
			$order->fetch($invoice->fk_commande);
		}

		if (!$order || $order->id == 0) {
			return 0; // No linked order
		}

		$order->fetch_optionals();
		
		$order_has_tracking = !empty($order->array_options['options_tracking_awb']);
		
		if (!$order_has_tracking) {
			return 0; // Order has no tracking, nothing to compare
		}

		// Compare tracking
		$invoice_awb = $invoice->array_options['options_tracking_awb'];
		$order_awb = $order->array_options['options_tracking_awb'];

		if ($invoice_awb != $order_awb) {
			// Invoice has different tracking than order - this is a warning
			dol_syslog("Invoice " . $invoice->ref . " has different tracking (" . $invoice_awb . ") than linked order " . $order->ref . " (" . $order_awb . ")", LOG_WARNING);
			
			// Note: We don't set a message here as this is a trigger, not a direct user action
			// The warning will be shown in the UI when viewing the invoice
		}

		return 0; // No errors
	}
}
