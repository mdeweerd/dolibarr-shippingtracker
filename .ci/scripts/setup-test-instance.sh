# Copyright (C) 2026 MDW <mdeweerd@users.noreply.github.com>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.
# Loads the demo database of the pinned Dolibarr release into the MariaDB
# service and writes conf.php, so the test workflow can run the module
# against a real Dolibarr. Adapted from the Dolibarr community-modules
# instance action, in the layout of mdeweerd/dolibarr-mcp (GitHub
# Actions): the Dolibarr sources come from the actions/cache entry of the
# workflow (DOLIBARR_ROOT), the demo dump is retagged with step5.php.
set -euo pipefail

: "${DB_HOST:=127.0.0.1}" ; : "${DB_PORT:=3306}" ; : "${DB_NAME:=dolibarr}"
: "${DB_USER:=root}" ; : "${DB_PASSWORD:=dolibarr}"
: "${DOLIBARR_TEST_REF:?must be set (see the workflow env)}"
: "${DOLIBARR_ROOT:=/home/runner/dolibarr}"
export DOLIBARR_HTDOCS="${DOLIBARR_ROOT}/htdocs"
export DOLIBARR_DOCUMENTS_ROOT="${DOLIBARR_ROOT}/documents"
root="${DOLIBARR_ROOT}"
major="${DOLIBARR_TEST_REF%%.*}"

mysql=(mysql --host "${DB_HOST}" --port "${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}")

# Give the MariaDB service time to accept connections.
for _ in $(seq 1 60); do
	if "${mysql[@]}" -e 'SELECT 1' >/dev/null 2>&1; then
		break
	fi
	echo "Waiting for the database at ${DB_HOST}..."
	sleep 2
done
"${mysql[@]}" -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8 COLLATE utf8_unicode_ci;"

# The release ships the dump of its own demo database at the .0 of its
# series; loading an older dump would mean driving the migration.
dump=$(find "${root}/dev/initdemo" -maxdepth 1 -name "mysqldump_dolibarr_${major}.*.sql" 2>/dev/null | sort | head -n 1)
if [ -z "${dump}" ]; then
	echo "ERROR: no demo dump for Dolibarr ${major} in ${root}/dev/initdemo/" >&2
	ls "${root}/dev/initdemo/" >&2 || true
	exit 1
fi
echo "Loading ${dump} into ${DB_NAME}"
# Dumps taken with a MySQL client open in sandbox mode carry a directive
# the MariaDB client refuses at line 1; it is dropped when present.
sed '1{/enable the sandbox mode/d;}' "${dump}" | "${mysql[@]}" "${DB_NAME}"

# The tests write documents: the demo umask is the one of the machine it
# was dumped on, and a stricter one makes the generation fail on a file
# it cannot read back.
"${mysql[@]}" "${DB_NAME}" -e "UPDATE llx_const SET value = '0666' WHERE name = 'MAIN_UMASK';"
# Some demo databases declare extrafields mandatory in NOT NULL columns:
# every update of an object is then refused for a field unrelated to the
# module. A plain instance has none, so they stop being mandatory here.
"${mysql[@]}" "${DB_NAME}" -e "UPDATE llx_extrafields SET fieldrequired = 0 WHERE fieldrequired <> 0;"
"${mysql[@]}" "${DB_NAME}" -N -B \
	-e "SELECT CONCAT('ALTER TABLE ', table_name, ' MODIFY ', column_name, ' ', column_type, ' NULL;')
	      FROM information_schema.columns
	     WHERE table_schema = DATABASE() AND table_name LIKE 'llx_%_extrafields'
	       AND is_nullable = 'NO' AND column_name NOT IN ('rowid', 'fk_object');" \
	| "${mysql[@]}" "${DB_NAME}"

# Configure the instance. No web server has run the installer here, and
# the core refuses to work while it believes it is mid-installation.
documents="${DOLIBARR_DOCUMENTS_ROOT}"
mkdir -p "${documents}/admin/temp" "${DOLIBARR_HTDOCS}/custom"
chmod -R a+rwX "${documents}"

# The first user of the demo dump (rowid 1) is a plain account - the
# administrators of the dump sit on later rows - while the setup pages
# of the module gate on $user->admin. The CI user is promoted the way
# a real instance promotes its administrator.
"${mysql[@]}" "${DB_NAME}" -e "UPDATE llx_user SET admin = 1 WHERE rowid = 1;"

# No password is typed in a CI: the pages are read as the first user of
# the instance (authentication=forceuser), who receives the module
# rights from .ci/scripts/activate-module.php.
autouser=$("${mysql[@]}" "${DB_NAME}" -N -B -e "SELECT login FROM llx_user WHERE rowid = 1;")
echo "Pages will be read as: ${autouser}"

cat > "${DOLIBARR_HTDOCS}/conf/conf.php" <<PHPCONF
<?php
\$dolibarr_main_url_root='http://127.0.0.1:8080';
\$dolibarr_main_document_root='${DOLIBARR_HTDOCS}';
\$dolibarr_main_url_root_alt='/custom';
\$dolibarr_main_document_root_alt='${DOLIBARR_HTDOCS}/custom';
\$dolibarr_main_data_root='${documents}';
\$dolibarr_main_db_host='${DB_HOST}';
\$dolibarr_main_db_port='${DB_PORT}';
\$dolibarr_main_db_name='${DB_NAME}';
\$dolibarr_main_db_prefix='llx_';
\$dolibarr_main_db_user='${DB_USER}';
\$dolibarr_main_db_pass='${DB_PASSWORD}';
\$dolibarr_main_db_type='mysqli';
\$dolibarr_main_db_character_set='utf8';
\$dolibarr_main_db_collation='utf8_unicode_ci';
\$dolibarr_main_authentication='forceuser';
\$dolibarr_auto_user='${autouser}';
\$dolibarr_main_prod='0';
\$dolibarr_main_instance_unique_id='cishippingtracker';
PHPCONF

# The demo dump is the .0 of the series (e.g. 24.0.0) while the core is a
# later patch of it (DOLIBARR_TEST_REF): main.inc.php redirects every
# page to the install screen while MAIN_VERSION_LAST_UPGRADE differs
# from DOL_VERSION. The workflow guard proved the branch carries no x.y.z
# migration scripts, so retagging the database with the core version is
# the whole upgrade - done with step5.php, the final step of Dolibarr's
# own upgrade path. The tag must actually land: fail, not warn.
dumpversion=$(basename "${dump}")
dumpversion="${dumpversion#mysqldump_dolibarr_}"
dumpversion="${dumpversion%.sql}"
echo "Retagging the demo database from ${dumpversion} to ${DOLIBARR_TEST_REF}"
# A restored sources cache also restores documents/install.lock from the
# previous run; step5 refuses to retag while it exists. It is re-created
# below once the retag has landed.
rm -f "${documents}/install.lock"
(cd "${DOLIBARR_HTDOCS}/install" && php step5.php "${dumpversion}" "${DOLIBARR_TEST_REF}")

tagged=$("${mysql[@]}" "${DB_NAME}" -N -B -e "SELECT value FROM llx_const WHERE name = 'MAIN_VERSION_LAST_UPGRADE';")
if [ "${tagged}" != "${DOLIBARR_TEST_REF}" ]; then
	echo "ERROR: step5 did not tag the demo database at ${DOLIBARR_TEST_REF} (got '${tagged}')." >&2
	exit 1
fi

# The install pages are locked only now (inc.php locks them as soon as
# this file exists).
touch "${documents}/install.lock"

echo "Instance ready: htdocs=${DOLIBARR_HTDOCS}, data=${documents}"
