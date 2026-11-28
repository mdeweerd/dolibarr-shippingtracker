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
 * Phan config for the module of this repository - Phan 6.x (latest). Run
 * from the repository root with the Dolibarr core checkout in dolibarr/
 * (see .github/workflows/analyse.yml). Adapted from the Dolibarr core
 * dev/tools/phan/config.php: only the module directories at the ROOT of
 * this repository are analysed, the core is parsed for context. The
 * legacy findings live in the committed .phan/baseline.txt generated on
 * the first workflow run: only NEW findings fail the job.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', __DIR__ . '/../dolibarr/htdocs');
}

return [
	'target_php_version' => '8.2',
	'backward_compatibility_checks' => false,
	'simplify_ast' => true,

	'globals_type_map' => [
		'action' => 'string',
		'bc' => 'array',
		'conf' => '\\Conf',
		'db' => '\\DoliDB',
		'extrafields' => '\\ExtraFields',
		'form' => '\\Form',
		'hookmanager' => '\\HookManager',
		'langs' => '\\Translate',
		'leftmenu' => 'string',
		'mainmenu' => 'string',
		'menumanager' => '\\MenuManager',
		'mysoc' => '\\Societe',
		'user' => '\\User',
		'dolibarr_main_document_root' => 'string',
		'dolibarr_main_db_type' => '?string',
	],

	// Symbols provided at runtime by the Dolibarr entry scripts: page
	// functions and the CSRF token helper. Same purpose as
	// .ci/dolibarr-runtime.php, which PHPStan loads through its own
	// bootstrap. The module lives at the root of the repository: its
	// entry pages are files, not a directory.
	// Paths relative to the repository root Phan runs from: Phan
	// identifies analysed files by exactly these paths, and the
	// generated baseline (load AND save) keys them the same way.
	'file_list' => [
		'.ci/dolibarr-runtime.php',
	],

	'directory_list' => [
		// Parsed for context, excluded from analysis below.
		'dolibarr/htdocs/core',
		'dolibarr/htdocs/fourn/class',
		'dolibarr/htdocs/expedition/class',
		'dolibarr/htdocs/user/class',
		// Analysed.
		'admin',
		'class',
		'core',
		'lib',
	],

	'exclude_analysis_directory_list' => [
		'dolibarr',
		'.ci',
	],

	// Single process: deterministic and within the memory budget of the
	// job (see the workflow). The parsed core is large but the analysed
	// module is small.
	'processes' => 1,
];
