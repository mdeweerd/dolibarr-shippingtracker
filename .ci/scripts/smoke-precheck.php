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
 * \file    .ci/scripts/smoke-precheck.php
 * \brief   Prints the module state the page smoke is about to read: a
 *          fresh CLI process loads the instance the way the served
 *          pages do, so a smoke failure next to a "module on" precheck
 *          points at the page path, not at the activation. Reads
 *          DOLIBARR_HTDOCS and MODULE from the environment (see
 *          .github/workflows/test.yml).
 */

if (PHP_SAPI !== 'cli') {
	echo "Error: this script must be run from the command line.\n";
	exit(1);
}

global $conf, $db, $langs, $user, $mysoc;

$htdocs = getenv('DOLIBARR_HTDOCS');
$module = getenv('MODULE');
if (!$htdocs || !file_exists($htdocs . '/master.inc.php') || !$module) {
	fwrite(STDERR, "DOLIBARR_HTDOCS and MODULE must point at the instance and its module (see .github/workflows/test.yml).\n");
	exit(2);
}

require_once $htdocs . '/master.inc.php';

$constname = 'MAIN_MODULE_' . strtoupper($module);
$value = getDolGlobalString($constname);
echo 'Precheck: const ' . $constname . ' = ' . ($value !== '' ? $value : '(not set)')
	. ', isModEnabled(' . $module . ') = ' . (isModEnabled($module) ? 'yes' : 'no')
	. ', entity = ' . (int) $conf->entity . "\n";

// The const rows themselves, whatever their entity or spelling: this
// is what a page rendered under another entity, or a const written
// under another name, would show.
$resql = $db->query(
	"SELECT name, value, entity FROM " . MAIN_DB_PREFIX . "const"
	. " WHERE name LIKE 'MAIN_MODULE_%' AND LOWER(name) LIKE '%" . $db->escape(strtolower($module)) . "%'"
);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		echo 'Precheck: const row ' . $obj->name . ' = ' . $obj->value . ' (entity ' . (int) $obj->entity . ")\n";
	}
	$db->free($resql);
} else {
	fwrite(STDERR, "Precheck: cannot read the const rows: " . $db->lasterror() . "\n");
}
