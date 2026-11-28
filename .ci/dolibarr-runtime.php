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
 * \file    .ci/dolibarr-runtime.php
 * \brief   Dolibarr runtime symbols used by the module code but defined
 *          only when the entry script htdocs/main.inc.php executes
 *          (constants of master.inc.php, page functions and the CSRF
 *          token helper of main.inc.php). The analysers never execute
 *          main.inc.php, so they load this file instead: PHPStan through
 *          .ci/phpstan-bootstrap.php, Phan through 'file_list' in
 *          .phan/config.php. The guards keep the file harmless against
 *          the real definitions.
 * \remarks Every new "unknown constant/function" failure of an analyser
 *          adds its symbol here with the signature copied from the core;
 *          silencing the analyser rule instead lets it stop checking
 *          real code.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', __DIR__ . '/../dolibarr/htdocs');
}
if (!defined('DOL_DATA_ROOT')) {
	define('DOL_DATA_ROOT', __DIR__ . '/../dolibarr/documents');
}
if (!defined('DOL_URL_ROOT')) {
	define('DOL_URL_ROOT', '');
}
if (!defined('DOL_MAIN_URL_ROOT')) {
	define('DOL_MAIN_URL_ROOT', '');
}
if (!defined('MAIN_DB_PREFIX')) {
	define('MAIN_DB_PREFIX', 'llx_');
}

// Signatures copied from htdocs/main.inc.php of the Dolibarr 24.0 branch.
if (!function_exists('llxHeader')) {
	/**
	 * @param string $head
	 * @param string $title
	 * @param string $help_url
	 * @param string $target
	 * @param int $disablejs
	 * @param int $disablehead
	 * @param string|array $arrayofjs
	 * @param string|array $arrayofcss
	 * @param string $morequerystring
	 * @param string $morecssonbody
	 * @param string $replacemainareaby
	 * @param int $disablenofollow
	 * @param int $disablenoindex
	 * @return void
	 */
	function llxHeader($head = '', $title = '', $help_url = '', $target = '', $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '', $morequerystring = '', $morecssonbody = '', $replacemainareaby = '', $disablenofollow = 0, $disablenoindex = 0)
	{
	}
}
if (!function_exists('llxFooter')) {
	/**
	 * @param string $comment
	 * @param string $zone
	 * @param int $disabledoutputofmessages
	 * @return void
	 */
	function llxFooter($comment = '', $zone = 'private', $disabledoutputofmessages = 0)
	{
	}
}
if (!function_exists('newToken')) {
	/**
	 * @return string
	 */
	function newToken()
	{
		return '';
	}
}
