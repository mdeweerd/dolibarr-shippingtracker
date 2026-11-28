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
 * \file    .ci/scripts/activate-module.php
 * \brief   Activates the module of this repository on the CI instance the
 *          way Home - Setup - Modules does, then grants the module rights
 *          to the CI user. Reads DOLIBARR_HTDOCS, MODULE_CLASS and
 *          RIGHTS_CLASS from the environment (see .github/workflows/test.yml).
 * \remarks activateModule() is what replays the activation data of the
 *          descriptor (sql/ statements, constants, menus, permissions),
 *          so this is also what gives the instance what the module works
 *          with. The off/on activation replay is proven by the module
 *          tests (PLAN.md, Phase 1), not by running this script again:
 *          on an enabled module activateModule is a no-op.
 */

if (PHP_SAPI !== 'cli') {
	echo "Error: this script must be run from the command line.\n";
	exit(1);
}

global $conf, $db, $langs, $user, $mysoc;

$htdocs = getenv('DOLIBARR_HTDOCS');
if (!$htdocs || !file_exists($htdocs . '/master.inc.php')) {
	fwrite(STDERR, 'DOLIBARR_HTDOCS does not point at an htdocs directory (got "' . $htdocs . '")' . "\n");
	exit(2);
}

$moduleclass = getenv('MODULE_CLASS');
$rightsclass = getenv('RIGHTS_CLASS');
if (!$moduleclass || !$rightsclass) {
	fwrite(STDERR, "MODULE_CLASS and RIGHTS_CLASS must be set (see .github/workflows/test.yml).\n");
	exit(2);
}

require_once $htdocs . '/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

$user = new User($db);
$user->fetch(1);

// Plain call: activation from scratch, or a clean no-op when the module
// is already enabled (activateModule then returns at once, harmlessly).
$result = activateModule($moduleclass);

if (!empty($result['errors'])) {
	fwrite(STDERR, 'Activation failed: ' . implode(' | ', $result['errors']) . "\n");
	exit(1);
}

echo 'Module ' . $rightsclass . ' activated (' . (int) $result['nbmodules'] . ' module(s), ' . (int) $result['nbperms'] . " permission(s))\n";

// The page smoke reads the module pages through the router, and those
// pages gate their content on isModEnabled(), which reads the const the
// activation must have written. Verify the row right here: an
// activation that silently wrote nothing then stops the job at its
// actual cause instead of at a missing page marker.
$constname = 'MAIN_MODULE_' . strtoupper($rightsclass);
$resql = $db->query(
	"SELECT value, entity FROM " . MAIN_DB_PREFIX . "const"
	. " WHERE name = '" . $db->escape($constname) . "'"
	. " AND entity IN (0, " . (int) $conf->entity . ")"
);
if (!$resql) {
	fwrite(STDERR, "Cannot read the " . $constname . " const: " . $db->lasterror() . "\n");
	exit(1);
}
$constrow = $db->fetch_object($resql);
$db->free($resql);
if (!$constrow || $constrow->value === null || $constrow->value === '') {
	fwrite(STDERR, "Activation wrote no " . $constname . " const row: isModEnabled() will stay false on every page.\n");
	exit(1);
}
echo 'Activation const ' . $constname . ' = ' . $constrow->value . ' (entity ' . (int) $constrow->entity . ")\n";

// The CI reads the pages as user 1 (conf.php forces the authentication to
// that user). A Dolibarr admin does not implicitly hold the rights of a
// module - User::loadRights() adds only the user-module rights to an
// admin - so the module rights are granted the way the Permissions tab of
// the user card does.
$resql = $db->query(
	"SELECT DISTINCT r.id FROM " . MAIN_DB_PREFIX . "rights_def AS r"
	. " WHERE r.module = '" . $db->escape($rightsclass) . "' AND r.entity = " . (int) $conf->entity
);
if (!$resql) {
	fwrite(STDERR, "Cannot list the " . $rightsclass . " rights: " . $db->lasterror() . "\n");
	exit(1);
}
$granted = 0;
while ($obj = $db->fetch_object($resql)) {
	if ($user->addrights((int) $obj->id, '', '', (int) $conf->entity) > 0) {
		$granted++;
	}
}
$db->free($resql);
echo "Granted " . $granted . " " . $rightsclass . " right(s) to user " . (int) $user->id . "\n";
if ($granted < 1) {
	fwrite(STDERR, "No " . $rightsclass . " right was granted to the CI user: the page smoke would read the module pages as a forbidden zone.\n");
	exit(1);
}
