<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Formatter;

use Composer\Pcre\Preg;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Neon\Neon;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/*
 * Translations going through TexyFormatter::substitute() (the safe |format Latte
 * filter / safe translate() PHP method) use a marker-based escape scheme that
 * supports %s placeholders only. With %d, %f, %x etc., vsprintf coerces the
 * opaque marker string before strtr restores the args - the marker is gone from
 * the rendered string, strtr finds nothing to replace, and the actual arg value
 * silently disappears (the rendered string shows 0 / 0.0).
 *
 * The unsafe path (substitutePossiblyUnsafeHtml / |formatPossiblyUnsafeHtml)
 * uses raw vsprintf where any specifier works, but routing the same translation
 * between paths over time would silently break under the safe path. So the rule
 * for all TexyFormatter-routed translations is "%s only".
 *
 * Scans every string in app/src/lang/*.neon for non-%s printf specifiers. Key
 * prefixes that are NOT routed through TexyFormatter (e.g. `forms` for Nette
 * Form::addRule messages where %label / %d / %value are Nette's own
 * placeholders, not vsprintf) get explicit allow-list entries with a reason.
 * Stale entries fail the test loudly.
 *
 * Negative lookahead in the specifier regex avoids matching Nette-style
 * placeholders like %label / %value / %count where the printf-style specifier
 * letter (l, v, c) is followed by another letter and so cannot be a real
 * printf specifier.
 */

/** @testCase */
final class TexyFormatterTranslationPlaceholdersAllowListTest extends TestCase
{

	private const string NON_S_SPECIFIER_PATTERN = '~%(?:\d+\$)?[-+0 #]*\d*(?:\.\d+)?[bcdeEfFgGoxXuU](?![a-zA-Z])~';


	public function testAllowList(): void
	{
		$langDir = realpath(__DIR__ . '/../../src/lang');
		if ($langDir === false) {
			throw new RuntimeException('Could not resolve app/src/lang/ directory');
		}
		$allowList = $this->allowList();
		$errors = [];
		$usedPrefixes = [];

		$files = glob("{$langDir}/*.neon");
		if ($files === false) {
			throw new RuntimeException('Could not enumerate lang files');
		}
		foreach ($files as $file) {
			$content = file_get_contents($file);
			if ($content === false) {
				throw new RuntimeException("Could not read {$file}");
			}
			$relativePath = 'lang/' . basename($file);
			$tree = Neon::decode($content);
			if (!is_array($tree)) {
				throw new RuntimeException("Unexpected NEON root in {$relativePath}");
			}
			$this->walk($tree, '', $relativePath, $allowList, $usedPrefixes, $errors);
		}

		foreach ($allowList as $prefix => $reason) {
			if (!isset($usedPrefixes[$prefix])) {
				$errors[] = sprintf("Stale allow-list prefix (no matching translation with a non-%%s specifier found): %s\n  reason: %s", $prefix, $reason);
			}
		}

		if ($errors !== []) {
			Assert::fail(implode("\n\n", $errors));
		}
	}


	/**
	 * @param array<int|string, mixed> $tree
	 * @param array<string, string> $allowList
	 * @param array<string, true> $usedPrefixes
	 * @param list<string> $errors
	 */
	private function walk(array $tree, string $keyPrefix, string $file, array $allowList, array &$usedPrefixes, array &$errors): void
	{
		foreach ($tree as $key => $value) {
			$path = $keyPrefix === '' ? (string)$key : $keyPrefix . '.' . $key;
			if (is_array($value)) {
				$this->walk($value, $path, $file, $allowList, $usedPrefixes, $errors);
				continue;
			}
			if (!is_string($value)) {
				continue;
			}
			if (!Preg::isMatch(self::NON_S_SPECIFIER_PATTERN, $value)) {
				continue;
			}
			$allowedPrefix = $this->findAllowedPrefix($path, $allowList);
			if ($allowedPrefix !== null) {
				$usedPrefixes[$allowedPrefix] = true;
				continue;
			}
			$errors[] = sprintf(
				"Non-%%s printf specifier in translation that may go through TexyFormatter::substitute(): %s\n  key:   %s\n  value: %s\n  → Change to %%s and pass (string)\$arg at the call site, or add an allow-list entry for the key prefix with a reason.",
				$file,
				$path,
				$value,
			);
		}
	}


	/**
	 * Returns the longest matching allow-list prefix for the dotted key path,
	 * or null if no prefix covers it. Prefix `forms` matches `forms` and
	 * `forms.anything.deeper`, but not `formsExtra`.
	 *
	 * @param array<string, string> $allowList
	 */
	private function findAllowedPrefix(string $key, array $allowList): ?string
	{
		$bestMatch = null;
		foreach (array_keys($allowList) as $prefix) {
			if ($key === $prefix || str_starts_with($key, $prefix . '.')) {
				if ($bestMatch === null || strlen($prefix) > strlen($bestMatch)) {
					$bestMatch = $prefix;
				}
			}
		}
		return $bestMatch;
	}


	/**
	 * Translation key prefix (dotted, e.g. `forms` matches `forms.*`) => reason
	 * why translations under that prefix may use non-%s placeholders.
	 *
	 * @return array<string, string>
	 */
	private function allowList(): array
	{
		return [
			'forms' => "Nette Form::addRule messages: %label, %d, %value are placeholders substituted by Nette's form validation rules, not vsprintf, so these translations never flow through TexyFormatter",
		];
	}

}

TestCaseRunner::run(TexyFormatterTranslationPlaceholdersAllowListTest::class);
