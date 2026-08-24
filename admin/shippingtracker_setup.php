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
 * \file    admin/shippingtracker_setup.php
 * \ingroup shippingtracker
 * \brief   ShippingTracker setup page
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res && file_exists("../../../../main.inc.php")) {
	$res = @include "../../../../main.inc.php";
}
if (!$res && file_exists("../../../../../main.inc.php")) {
	$res = @include "../../../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

// Translations
$langs->loadLangs(array("admin", "shippingtracker@shippingtracker"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
/** @var HookManager $hookmanager */
$hookmanager->initHooks(array('shippingtrackersetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');

$error = 0;
$setupnotempty = 0;

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->admin) {
	accessforbidden();
}

/*
 * Actions
 */

if ($action == 'set') {
	// Activate module
	if (dolibarr_set_const($db, 'MAIN_MODULE_ShippingTracker', GETPOST('value', 'alpha'), 'chaine', 0, '', $conf->entity) > 0) {
		// Ensure extrafields are created when module is enabled
		require_once __DIR__ . '/../lib/handlers/shipping_handler.php';
		ensure_tracking_extrafields('commande_fournisseur');
		ensure_tracking_extrafields('facture_fourn');

		$conf->shippingtracker = GETPOST('value', 'alpha');
	}
	
	if (!GETPOST('notrigger', 'int')) {
		// Force activation of triggers
		$res = $hookmanager->initHooks(array('shippingtrackersetup'));
	}
}

if ($action == 'setmod') {
	// TODO: Add module-specific settings if needed
}

/*
 * View
 */

$form = new Form($db);

// Module header
$title = $langs->trans("Module104300Name");
$short_title = $langs->trans("Module104300Name");

// Add a help button
$help_url = '';
$picto = 'generic';

llxHeader('', $title, $help_url);

// Print the form for the config
$form = new Form($db);

print load_fiche_titre($langs->trans("Module104300Name"), $help_url, $picto);

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

// Module activation
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ActivateModule").'</td>';
print '<td>';
if (isModEnabled('shippingtracker')) {
	print $form->selectyesno("MAIN_MODULE_ShippingTracker", 1, 1);
} else {
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=activate">'.$langs->trans("Activate").'</a>';
}
print '</td>';
print '</tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

// Module description
print '<br>';
print '<div class="fiche">';
print '<div class="tabBar">';
print '<span class="tabTitle">'.$langs->trans("ModuleDescription").'</span>';
print '</div>';
print '<div class="underbanner clearboth"></div>';
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<p>'.$langs->trans("ShippingTrackerDescriptionLong").'</p>';
print '</div>';
print '</div>';
print '</div>';

// Features
print '<br>';
print '<div class="fiche">';
print '<div class="tabBar">';
print '<span class="tabTitle">'.$langs->trans("Features").'</span>';
print '</div>';
print '<div class="underbanner clearboth"></div>';
print '<div class="fichecenter">';
print '<ul>';
print '<li>'.$langs->trans("Adds tracking number and carrier information to supplier orders and invoices").'</li>';
print '<li>'.$langs->trans("Automatically syncs tracking from orders to linked invoices").'</li>';
print '<li>'.$langs->trans("Supports all carriers configured in the expedition module").'</li>';
print '<li>'.$langs->trans("Generates tracking URLs automatically based on carrier").'</li>';
print '</ul>';
print '</div>';
print '</div>';

// Dependencies check
print '<br>';
print '<div class="fiche">';
print '<div class="tabBar">';
print '<span class="tabTitle">'.$langs->trans("Dependencies").'</span>';
print '</div>';
print '<div class="underbanner clearboth"></div>';
print '<div class="fichecenter">';

// Check expedition module
$expedition_enabled = isModEnabled('expedition');
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("Status").'</td>';
print '<td>'.$langs->trans("Required").'</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>Expedition (modExpedition)</td>';
print '<td>'.($expedition_enabled ? '<span class="ok">'.$langs->trans("Enabled").'</span>' : '<span class="error">'.$langs->trans("Disabled").'</span>').'</td>';
print '<td>'.$langs->trans("Yes").'</td>';
print '</tr>';
print '</table>';

if (!$expedition_enabled) {
	print '<div class="warning">'.$langs->trans("WarningModuleNotEnabled", "Expedition").'</div>';
}

print '</div>';
print '</div>';

// Footer
llxFooter();
$db->close();
