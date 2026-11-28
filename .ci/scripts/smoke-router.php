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
 * \file    .ci/scripts/smoke-router.php
 * \brief   Router of the smoke server (.ci/scripts/smoke-pages.sh)
 *
 * The built-in server runs every request from the directory it was
 * started in: it does not work from the directory of the requested
 * script the way Apache does, and the module pages locate main.inc.php
 * through paths relative to their own directory ("../../main.inc.php"
 * and friends), guarded by file_exists() calls that resolve against
 * the working directory. Execute the requested PHP page here, after
 * moving to its directory, and keep the server from serving the page
 * a second time. Anything else (static files, directories) falls back
 * to the server itself.
 */

$__smoke_uri = isset($_SERVER['REQUEST_URI']) ? explode('?', $_SERVER['REQUEST_URI'], 2)[0] : '';
$__smoke_file = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . rawurldecode($__smoke_uri);
if (is_file($__smoke_file) && substr($__smoke_file, -4) === '.php') {
	chdir(dirname($__smoke_file));
	$_SERVER['SCRIPT_FILENAME'] = $__smoke_file;
	$_SERVER['SCRIPT_NAME'] = $__smoke_uri;
	$_SERVER['PHP_SELF'] = $__smoke_uri;
	require $__smoke_file;
	return true;
}
return false;
