<?php
/**
 * \file    test/phpunit/BootstrapIncludeTest.php
 * \brief   Guards the single bootstrap entry point: every web entry page of
 *          the module must load the Dolibarr environment through
 *          core/bootstrap.php via a __DIR__-relative include, never by
 *          resolving main.inc.php itself.
 */

use PHPUnit\Framework\TestCase;

/**
 * Checks the bootstrap contract of the module pages.
 */
class BootstrapIncludeTest extends TestCase
{
	/**
	 * Module root (two levels up from test/phpunit).
	 *
	 * @return string
	 */
	private static function moduleRoot(): string
	{
		return dirname(__DIR__, 2);
	}

	/**
	 * Web entry pages of the module: root-level *.php and admin/*.php.
	 *
	 * @return string[] Absolute paths
	 */
	private static function entryPages(): array
	{
		$root = self::moduleRoot();
		$pages = array_merge(
			(array) glob($root.'/*.php'),
			(array) glob($root.'/admin/*.php')
		);
		sort($pages);
		return $pages;
	}

	public function testThereIsAtLeastOneEntryPage(): void
	{
		$this->assertNotEmpty(self::entryPages(), 'No web entry page found at module root or admin/');
	}

	public function testPagesDoNotResolveMainIncThemselves(): void
	{
		foreach (self::entryPages() as $page) {
			$code = (string) file_get_contents($page);
			$this->assertSame(
				0,
				preg_match('/(require|include)(_once)?[^;\n]*main\.inc\.php/i', $code),
				basename($page).' resolves main.inc.php itself instead of using core/bootstrap.php'
			);
		}
	}

	public function testPagesIncludeBootstrapViaDirRelativePath(): void
	{
		foreach (self::entryPages() as $page) {
			$code = (string) file_get_contents($page);
			$this->assertSame(
				1,
				preg_match('/require_once\s+__DIR__\s*\.\s*\'\/(\.\.\/)?core\/bootstrap\.php\'\s*;/', $code),
				basename($page).' does not include core/bootstrap.php through a __DIR__-relative path'
			);
		}
	}

	public function testBootstrapAnchorsCandidatesToItsOwnDir(): void
	{
		$bootstrap = self::moduleRoot().'/core/bootstrap.php';
		$this->assertFileExists($bootstrap);
		$code = (string) file_get_contents($bootstrap);
		$this->assertStringContainsString(
			'__DIR__."/../../"',
			$code,
			'core/bootstrap.php lacks a __DIR__-anchored candidate for main.inc.php'
		);
	}

	public function testBootstrapDeclaresNoGlobalFunction(): void
	{
		// Several modules may ship this same bootstrap in one request; a
		// global function declaration would fatal with "Cannot redeclare".
		$code = (string) file_get_contents(self::moduleRoot().'/core/bootstrap.php');
		$this->assertSame(
			0,
			preg_match('/^\s*function\s+\w+\s*\(/m', $code),
			'core/bootstrap.php declares a global function; keep helpers local (e.g. closures)'
		);
	}
}
