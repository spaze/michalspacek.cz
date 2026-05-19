<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Test\TestCaseRunner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/*
 * Companion to app/disallowed-calls.neon's ban on TexyFormatter::substitute() / translate():
 * same concern (Texy processing of args that may be user-controlled), different surface.
 * CLAUDE.md describes the review rule - flag any Filters::formatPossiblyUnsafeHtml() arg in
 * a template (the |formatPossiblyUnsafeHtml filter) where the variable may be user-controlled.
 *
 * Covers: .latte templates under app/src/.
 * Does not cover: PHP call sites of TexyFormatter::substitute() / translate() (see
 * disallowed-calls.neon's allowInMethods enforcement), Texy content stored in the DB,
 * and multi-line Latte tags (the scan is line-based; current call sites all fit on one line).
 */

/** @testCase */
final class FiltersFormatPossiblyUnsafeHtmlAllowListTest extends TestCase
{

	public function testAllowList(): void
	{
		$srcDir = realpath(__DIR__ . '/../../src');
		if ($srcDir === false) {
			throw new RuntimeException('Could not resolve app/src/ directory');
		}
		$allowList = $this->allowList();
		$errors = [];

		// Validate allow-list shape
		foreach ($allowList as $path => $entries) {
			foreach (array_keys($entries) as $needle) {
				if (!str_contains($needle, '|formatPossiblyUnsafeHtml')) {
					$errors[] = "Malformed allow-list needle (must contain '|formatPossiblyUnsafeHtml'): {$path}: '{$needle}'";
				}
			}
		}

		// Scan .latte files
		$usedEntries = [];
		$existingPaths = [];
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
		foreach ($iter as $file) {
			if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'latte') {
				continue;
			}
			$realPath = realpath($file->getPathname());
			if ($realPath === false) {
				continue;
			}
			$relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($realPath, strlen($srcDir) + 1));
			$existingPaths[$relativePath] = true;

			$lines = file($file->getPathname());
			if ($lines === false) {
				throw new RuntimeException("Could not read {$relativePath}");
			}
			foreach ($lines as $lineNoZeroBased => $line) {
				if (!str_contains($line, '|formatPossiblyUnsafeHtml') || !str_contains($line, '$')) {
					continue;
				}
				$matched = false;
				foreach ($allowList[$relativePath] ?? [] as $needle => $_) {
					if (str_contains($line, $needle)) {
						$usedEntries[$relativePath][$needle] = true;
						$matched = true;
					}
				}
				if (!$matched) {
					$lineNo = $lineNoZeroBased + 1;
					$errors[] = "Unreviewed |formatPossiblyUnsafeHtml with \$ inside: {$relativePath}:{$lineNo}\n  → " . trim($line) . "\n  Add an entry to ALLOW_LIST with a reason explaining why the variable is admin/dev-controlled.";
				}
			}
		}

		// Stale path / needle detection
		foreach ($allowList as $path => $entries) {
			if (!isset($existingPaths[$path])) {
				$reasons = implode(', ', array_values($entries));
				$errors[] = "Stale allow-list path (file no longer exists): {$path}\n  entries: {$reasons}";
				continue;
			}
			foreach ($entries as $needle => $reason) {
				if (!isset($usedEntries[$path][$needle])) {
					$errors[] = "Stale allow-list entry (no matching line found): {$path}\n  needle: {$needle}\n  reason: {$reason}";
				}
			}
		}

		if ($errors !== []) {
			Assert::fail(implode("\n\n", $errors));
		}
	}


	/**
	 * Path relative to app/src/ => needle => reason why those args are safe.
	 *
	 * Needles are matched as substrings of the source line and must contain
	 * `|formatPossiblyUnsafeHtml`. Reason text surfaces in stale-entry errors.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function allowList(): array
	{
		return [
			// Populated when templates are migrated. Empty initially - there are zero
			// |formatPossiblyUnsafeHtml usages with a `$` inside the tag yet.
		];
	}

}

TestCaseRunner::run(FiltersFormatPossiblyUnsafeHtmlAllowListTest::class);
