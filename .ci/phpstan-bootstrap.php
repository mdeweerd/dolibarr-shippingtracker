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
 * \file    .ci/phpstan-bootstrap.php
 * \brief   PHPStan bootstrap for the module of this repository. Mirrors
 *          Dolibarr's dev/build/phpstan/bootstrap_action.php for this
 *          layout: the module lives at the root of the repository, the
 *          Dolibarr core checkout sits in dolibarr/ (see
 *          .github/workflows/analyse.yml).
 */

if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}
if (!defined('NOHTTPSREDIRECT')) {
	define('NOHTTPSREDIRECT', '1');
}

global $dolibarr_main_document_root;
$dolibarr_main_document_root = __DIR__ . '/../dolibarr/htdocs';
define('DOL_DOCUMENT_ROOT', __DIR__ . '/../dolibarr/htdocs');
define('DOL_DATA_ROOT', __DIR__ . '/../dolibarr/documents');
define('DOL_URL_ROOT', '/');
define('DOL_MAIN_URL_ROOT', '/');
define('MAIN_DB_PREFIX', 'llx_');

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
global $conf, $db, $hookmanager, $langs, $mysoc, $user;

// Constants and the page functions are provided at runtime by the
// included main.inc.php. Both analysers (PHPStan here, Phan through
// 'file_list' in .phan/config.php) declare them once in
// dolibarr-runtime.php so the analysis scope knows them.
require_once __DIR__ . '/dolibarr-runtime.php';
