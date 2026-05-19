<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Composer\Pcre\Preg;
use MichalSpacekCz\Test\TestCaseRunner;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/*
 * Companion to TexyFormatter::createTexy()'s urlSchemeFilters[FILTER_ANCHOR] = '#https?:#Ai'.
 * Scans .latte templates and lang/*.neon translation files for non-http(s) URL schemes that
 * would land in a Texy-processed <a href>. Two context filters drop the structural noise:
 *  - lines with `<a href="..."` in Latte (direct HTML, Texy doesn't see it)
 *  - NEON key-value lines like `skype: Skype` (the colon is YAML, not a URL scheme separator)
 * Anything still matching needs an allow-list entry or, better, a switch to a bare email
 * arg that LinkModule::solve()'s email branch auto-mailto's before checkURL runs.
 *
 * Covers: .latte templates under app/src/, lang/*.neon translation files.
 * Does not cover: Texy content stored in the DB (scan that out-of-band with SQL, any
 * Texy column rendered by TexyFormatter shares the filter), other .neon files under
 * app/config (parameters, CSP, services etc. mention URL schemes in unrelated contexts),
 * and PHP source files (explicit URL-arg literals are caught by code review, not this scan).
 */

/** @testCase */
final class TexyFormatterAnchorSchemeAllowListTest extends TestCase
{

	private const array NON_HTTPS_SCHEMES = [
		'about',
		'callto',
		'chrome',
		'data',
		'feed',
		'file',
		'ftp',
		'ftps',
		'git',
		'intent',
		'irc',
		'ircs',
		'javascript',
		'jscript', // IE-only JavaScript alias, executed scripts in <a href> back in the day
		'livescript', // Netscape's name for JavaScript before the rename
		'magnet',
		'mailto',
		'mocha', // Netscape pre-JavaScript codename
		'news',
		'nntp',
		'sftp',
		'skype',
		'sms',
		'spotify',
		'tel',
		'vbscript', // IE-only VBScript URLs - real XSS vector ~1999-2017
		'view-source', // browser-internal scheme; some chains used to deref via <a href>
		'webcal',
		'ws',
		'wss',
		'xmpp',
	];


	public function testAllowList(): void
	{
		$srcDir = realpath(__DIR__ . '/../../src');
		if ($srcDir === false) {
			throw new RuntimeException('Could not resolve app/src/ directory');
		}
		$schemePattern = '#\b(' . implode('|', self::NON_HTTPS_SCHEMES) . '):#';
		$allowList = $this->allowList();
		$errors = [];

		// Validate allow-list shape
		foreach ($allowList as $path => $entries) {
			foreach (array_keys($entries) as $needle) {
				if (!Preg::isMatch($schemePattern, $needle)) {
					$errors[] = "Malformed allow-list needle (must contain a non-http(s) URL scheme): {$path}: '{$needle}'";
				}
			}
		}

		$usedEntries = [];
		$existingPaths = [];
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir));
		foreach ($iter as $file) {
			if (!$file instanceof SplFileInfo || !$file->isFile()) {
				continue;
			}
			$ext = $file->getExtension();
			if ($ext !== 'latte' && $ext !== 'neon') {
				continue;
			}
			$realPath = realpath($file->getPathname());
			if ($realPath === false) {
				continue;
			}
			$relativePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($realPath, strlen($srcDir) + 1));
			// Restrict NEON scan to translation files; other .neon (config, parameters, CSP)
			// commonly mention URL schemes in unrelated contexts.
			if ($ext === 'neon' && !str_starts_with($relativePath, 'lang/')) {
				continue;
			}
			$existingPaths[$relativePath] = true;

			$lines = file($file->getPathname());
			if ($lines === false) {
				throw new RuntimeException("Could not read {$relativePath}");
			}
			foreach ($lines as $lineNoZeroBased => $line) {
				if (!Preg::isMatch($schemePattern, $line)) {
					continue;
				}
				// Mask out direct HTML <a href="scheme:..."> attributes in Latte. Texy doesn't
				// process those, so they shouldn't count. Mask rather than skip-the-line so a
				// real Texy-bound scheme later on the same line still gets caught. Handles
				// both " and ' quoting via the backreference.
				if ($ext === 'latte') {
					$line = Preg::replace('#<a\s[^>]*href=(["\'])[a-z][a-z0-9+.-]*:[^"\']*\1#i', '', $line);
				}
				// NEON key-label lines like `skype: Skype` - the colon after the key is YAML,
				// not a URL. The value can be multi-word but must contain no colon (rules out
				// `foo: mailto:bad@x`, accepts `skype: Skype Profile`).
				if ($ext === 'neon' && Preg::isMatch('#^\s*[a-z][a-z0-9_-]*:\s*[^:]*$#i', $line)) {
					continue;
				}
				if (!Preg::isMatch($schemePattern, $line)) {
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
					$errors[] = "Non-http(s) URL scheme in source where Texy would render it: {$relativePath}:{$lineNo}\n  → " . trim($line) . "\n  TexyFormatter::createTexy() restricts <a href> schemes to http(s). For emails, switch to a bare 'mail@host' arg (LinkModule's email branch prepends 'mailto:' before checkURL runs). For anything else, add an entry to allowList() with a reason explaining why the scheme is intentional, or rework the call site.";
				}
			}
		}

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
	 * Path relative to app/src/ => needle => reason why this non-http(s) scheme is safe.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function allowList(): array
	{
		return [
			// Empty. The two structural filters in testAllowList() already drop direct-HTML
			// <a href=...> lines and NEON key labels, so the only remaining hits would be
			// scheme:url strings that actually reach Texy.
		];
	}

}

TestCaseRunner::run(TexyFormatterAnchorSchemeAllowListTest::class);
