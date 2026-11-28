# Working conventions - shippingtracker

Load and follow this file before touching anything in this repository.

## Locations

- Specification (single source of truth): docs/SPECIFICATION.md. Never
  build a feature that is not in it; when the scope changes, update it in
  the same commit as the code, with the FR number in the commit message.
- Plan and phasing: PLAN.md, with a short "Done" paragraph appended
  under each item as it is delivered.
- Steps in progress only: TODO.md. Purge once done; completed work lives
  in the git history and in the action log.
- CI: .github/workflows/ (lint.yml, analyse.yml, test.yml), scripts and
  analyser stubs in .ci/, analysers configured by phpstan.neon.dist and
  .phan/config.php.
- The module lives at the ROOT of this repository; in CI it is deployed
  as a real copy into htdocs/custom/shippingtracker/ (test.yml).

## Pinned references

- Analysis: Dolibarr 24.0 (DOLIBARR_REF, analyse.yml), the core
  directories the module calls are scanned for symbols.
- Instance tests: Dolibarr 24.0.4 (DOLIBARR_TEST_REF, test.yml), demo
  dump of the series retagged with install/step5.php, MariaDB 11.4
  service.
- PHP 8.3 for every job; PHPUnit 9.6, one process per test file.

## Commit discipline

Work done outside a developer clone is recorded through the repository
hosting API with factual commit messages, and the pushed content is
verified byte for byte after every push. Never leave completed work
uncommitted "because it is only documentation": the specification, the
plan, TODO and code all land in commits.

## Issues

Any problem or task not tracked in TODO.md or PLAN.md becomes an issue
with a short factual title and labels (bug, feature, spec, infra,
security, question). When picked up, mirror it in TODO.md and reference
the issue number in the commits. A security finding is an issue with
the security label, never a code comment that silently documents a
weakness.

## Action log

One entry per session (date, goal, actions, files and objects touched,
decisions, next step) on the maintainer's log page outside this
repository. Append only: never rewrite or delete a past entry.

## Sub-agents

Bounded written objectives (files, sections, checks), and the action log
states which sub-agent produced what.

## Development constraints (quick reference)

- No Dolibarr core file modification; hooks, triggers and tabs only.
- Every UI string in the lang files (fr_FR first, en_US second); help
  keys carry a Help marker in their name and plain text only.
- Every POST form carries the CSRF token; a GET that changes state
  carries it too.
- Activation statements are idempotent (CREATE TABLE IF NOT EXISTS,
  guarded inserts): the activation machinery replays at every activation.
- An admin does not implicitly hold the module rights: CI grants them to
  the CI user explicitly (.ci/scripts/activate-module.php).
- Tests: one process per test file, everything touched restored in
  tearDown, no assertion on hardcoded rowids of the reference dataset.
- Page smoke: 200 AND no PHP error in body and server log AND the
  expected marker of the feature (.ci/scripts/smoke-pages.sh).
- Every new "unknown constant/function" of an analyser adds its symbol
  to .ci/dolibarr-runtime.php with the signature copied from the core;
  never silence the analyser rule instead.

## Definition of done

The specification, plan and TODO exist and are committed; the pipeline
is green end to end (lint, pre-commit, analysis from the baselines,
instance tests, page smoke) on every run; and the first action log entry
records the pinned versions and job names. The security audit of the
delivered surface ran with the task: what it exposes, who may reach it,
who must not (verified as a failing forbidden case), inputs validated,
outputs escaped, data isolated. Its findings are a fix or a tracked
issue; "it works" is not "it is safe".
