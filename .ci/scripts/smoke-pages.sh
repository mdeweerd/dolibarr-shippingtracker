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
# Serves the CI instance and opens the pages the module adds, requiring
# each one to answer 200, hold no PHP error, and carry the marker of its
# feature: a page that answers 200 with the error inside (or a forbidden
# zone, which Dolibarr also answers with 200) is a failure, not a pass.
# Adapted from the Dolibarr community-modules smoke action.
set -uo pipefail

htdocs="${DOLIBARR_HTDOCS:?DOLIBARR_HTDOCS must point at the htdocs directory}"
port="${SMOKE_PORT:-8080}"
log="${SMOKE_LOG:-php-server.log}"

# Through a router on purpose: the built-in server serves from the
# directory it was started in, while the module pages include
# main.inc.php through paths relative to their own directory. The
# router runs each requested page from the directory of that page,
# the way Apache serves.
router="$(cd "$(dirname "$0")" && pwd)/smoke-router.php"
php -S "127.0.0.1:${port}" -t "${htdocs}" "${router}" > "${log}" 2>&1 &
server=$!
trap 'kill "${server}" 2>/dev/null || true' EXIT
for _ in $(seq 1 30); do
	if curl -fsS -o /dev/null "http://127.0.0.1:${port}/index.php"; then
		break
	fi
	sleep 1
done

# What the served pages will gate on: a fresh CLI process reads the
# same instance the way the pages do. A page that answers 200 without
# its marker while this prints the module on points at the page path,
# not at the activation, so keep the precheck output next to the
# failures.
php "$(dirname "$0")/smoke-precheck.php" || true

# The precheck above reads the instance from the command line; this
# probe reads it the way the served pages do - same router, same
# login, same conf loading - and prints the module state of the HTTP
# process, so a failing page next to a probe that sees the module on
# points at the page alone. Removed right after: the probe is a
# diagnostic, not an instance file.
probe="${htdocs}/custom/__smoke_probe.php"
cat > "${probe}" <<'PROBE'
<?php
$res = 0;
if (file_exists('../main.inc.php')) {
	$res = @include '../main.inc.php';
}
if (!$res) {
	echo "Probe: include of main.inc.php fails\n";
	exit(1);
}
$module = getenv('MODULE');
echo 'Probe: entity=' . (int) $conf->entity
	. ' user=' . (int) $user->id . (!empty($user->admin) ? ' (admin)' : '')
	. ' module=' . $module
	. ' const=' . (getDolGlobalString('MAIN_MODULE_' . strtoupper($module)) ?: '(not set)')
	. ' isModEnabled=' . (isModEnabled($module) ? 'yes' : 'no') . "\n";
echo "SMOKE_PROBE_OK\n";
PROBE
curl -sS "http://127.0.0.1:${port}/custom/__smoke_probe.php"
rm -f "${probe}"

# Core pages first, then the pages of the module; the module pages are
# read as user 1, who holds the module rights after
# .ci/scripts/activate-module.php granted them the way the Permissions
# tab of the user card does.
pages=(
	"/index.php"
	"/admin/modules.php"
	"/custom/shippingtracker/admin/shippingtracker_setup.php"
)
for page in ${MODULE_SMOKE_PAGES:-}; do
	pages+=("${page}")
done

tmp=$(mktemp -d)
failed=0
for page in "${pages[@]}"; do
	body="${tmp}/page-$(echo "${page}" | tr '/?=&' '____').html"
	code=$(curl -sS -o "${body}" -w '%{http_code}' "http://127.0.0.1:${port}${page}")
	# The built-in server writes the PHP errors to its own log, and
	# Dolibarr prints them in the page as well when it is not in
	# production mode: both are read.
	if [ "${code}" != "200" ]; then
		# A 404 or a 500 has no Location header: the empty grep would end
		# the step before the error line under pipefail, so it may find
		# nothing.
		location=$(curl -sS -o /dev/null -D - "http://127.0.0.1:${port}${page}" | { grep -i '^location:' || true; } | tr -d '\r')
		echo "ERROR: ${page} answered ${code} ${location}"
		failed=1
	elif grep -qiE '(Fatal error|Parse error|Uncaught [A-Za-z]*(Error|Exception))' "${body}"; then
		echo "ERROR: ${page} holds a PHP error"
		grep -iE -m3 '(Fatal error|Parse error|Uncaught )' "${body}" | cut -c1-300
		failed=1
	else
		# A page that answers 200 without its feature is the "200 with
		# the error inside" failure the CI must catch: each module page
		# carries the marker of its feature below.
		expect=''
		case "${page}" in
			*/shippingtracker_setup.php) expect='MAIN_MODULE_ShippingTracker' ;;
		esac
		if [ -n "${expect}" ] && ! grep -qF -- "${expect}" "${body}"; then
			# The page text says what the markers cannot: an empty shell
			# of main.inc.php, a forbidden zone or an unexpected answer all
			# look the same without it.
			text=$(perl -0777 -pe 's/<script\b[^>]*>.*?<\/script>//gs; s/<style\b[^>]*>.*?<\/style>//gs' "${body}" | sed 's/<[^>]*>/ /g' | tr -s ' \t\n\r' ' ' | cut -c1-340)
			echo "ERROR: ${page} answers 200 without its expected marker ('${expect}' missing): ${text}"
			failed=1
		else
			echo "  200  ${page}  ($(wc -c < "${body}") bytes)"
		fi
	fi
done

if grep -qiE '(PHP Fatal error|PHP Parse error)' "${log}"; then
	echo "ERROR: the server log holds a PHP error"
	grep -iE -m5 '(PHP Fatal error|PHP Parse error)' "${log}" | cut -c1-300
	failed=1
fi

rm -rf "${tmp}"
exit "${failed}"
