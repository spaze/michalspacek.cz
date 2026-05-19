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
		foreach ($allowList as $path => $snippets) {
			foreach (array_keys($snippets) as $snippet) {
				if (!str_contains($snippet, '|formatPossiblyUnsafeHtml')) {
					$errors[] = sprintf("Malformed allow-list snippet (must contain '|formatPossiblyUnsafeHtml'): %s: '%s'", $path, $snippet);
				}
			}
		}

		// Scan .latte files
		$usedSnippets = [];
		$existingPaths = [];
		foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srcDir)) as $file) {
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
				foreach ($allowList[$relativePath] ?? [] as $snippet => $_) {
					if (str_contains($line, $snippet)) {
						$usedSnippets[$relativePath][$snippet] = true;
						$matched = true;
					}
				}
				if (!$matched) {
					$lineNo = $lineNoZeroBased + 1;
					$message = "Unreviewed |formatPossiblyUnsafeHtml with \$ inside: %s:%s\n"
						. "  → %s\n"
						. '  Add a snippet to ALLOW_LIST with a reason explaining why the variable is admin/dev-controlled.';
					$errors[] = sprintf($message, $relativePath, $lineNo, trim($line));
				}
			}
		}

		// Stale path / snippet detection
		foreach ($allowList as $path => $snippets) {
			if (!isset($existingPaths[$path])) {
				$reasons = implode(', ', array_values($snippets));
				$errors[] = sprintf("Stale allow-list path (file no longer exists): %s\n  snippets: %s", $path, $reasons);
				continue;
			}
			foreach ($snippets as $snippet => $reason) {
				if (!isset($usedSnippets[$path][$snippet])) {
					$errors[] = sprintf("Stale allow-list snippet (no matching line found): %s\n  snippet: %s\n  reason: %s", $path, $snippet, $reason);
				}
			}
		}

		if ($errors !== []) {
			Assert::fail(implode("\n\n", $errors));
		}
	}


	/**
	 * Path relative to app/src/ => snippet => reason why those args are safe.
	 *
	 * Snippets are matched as substrings of the source line and must contain
	 * `|formatPossiblyUnsafeHtml`. Reason text surfaces in stale-snippet errors.
	 *
	 * @return array<string, array<string, string>>
	 */
	private function allowList(): array
	{
		return [
			'Presentation/Pulse/PasswordsStorages/default.latte' => [
				'{$algo->getNote()|formatPossiblyUnsafeHtml}' => 'admin-edited algorithm note (Texy) - format string itself is the $var, no args',
				'{$disclosure->getNote()|formatPossiblyUnsafeHtml}' => 'admin-edited disclosure note (Texy)',
				'{$site->getRecommendation()|formatPossiblyUnsafeHtml}' => 'admin-edited site recommendation (Texy)',
			],
			'Presentation/Www/CompanyTrainings/training.latte' => [
				'|formatPossiblyUnsafeHtml:"link:Www:Trainings:reviews {$training->getAction()}"' => 'admin-edited slug from trainings.action',
			],
			'Presentation/Www/Post/default.latte' => [
				'{=$item->getText()|formatPossiblyUnsafeHtml:$item->getUrl()}' => 'admin-edited blog "recommended reading" item (Texy text + URL)',
			],
			'Presentation/Www/Talks/talk.latte' => [
				'|formatPossiblyUnsafeHtml:"link:Www:Talks:talk {$talk->getAction()}"' => 'admin-edited slug from talks.action',
				'|formatPossiblyUnsafeHtml:$talk->getSupersededByTitle(),"link:Www:Talks:talk {$talk->getSupersededByAction()}"' => 'admin-edited talks.title (raw Texy ?string) + talks.action slug',
			],
			'Presentation/Www/Trainings/common/discontinued.latte' => [
				'{_$training->getDescription()|formatPossiblyUnsafeHtml:(string)$names, $training->getNewHref()}' => 'format string is admin-edited discontinued-training description (Texy); $names is a captured Texy fragment of bold training names; $newHref is admin URL',
			],
			'Presentation/Www/Trainings/files.latte' => [
				'|formatPossiblyUnsafeHtml:$trainingTitle, (string)$date' => '$trainingTitle is admin-edited training name (Html), $date is captured formatted date range',
				'"messages.trainings.moretrainings.$familiar"|formatPossiblyUnsafeHtml' => 'the $ is in the translation key ($familiar selects formal/informal), not in args; args are a literal Texy directive',
			],
			'Presentation/Www/Trainings/reviews.latte' => [
				'|formatPossiblyUnsafeHtml:"link:$link $name", $title, "link:Www:Trainings:training $name"' => '$link is a captured route prefix, $name is admin-edited trainings.action slug, $title is admin-edited trainings.name',
				'|formatPossiblyUnsafeHtml:"link:Www:CompanyTrainings:training $name"' => 'admin-edited trainings.action slug',
			],
			'Presentation/Www/Trainings/training.latte' => [
				'|formatPossiblyUnsafeHtml:$training->getName()' => 'admin-edited trainings.name (Html), passed through to render bold training name in date entry',
				'|formatPossiblyUnsafeHtml:"link:Www:CompanyTrainings:training {$training->getAction()}"' => 'admin-edited slug from trainings.action - same snippet appears twice in this file',
			],
			'Presentation/Www/Who/default.latte' => [
				"|formatPossiblyUnsafeHtml:\$talksApproxCount, 'https://www.webexpo.net', 'https://passwordscon.org/'" => '$talksApproxCount is an int from DB (talks count); URLs need Texy auto-linking so the whole call must use formatPossiblyUnsafeHtml',
			],
		];
	}

}

TestCaseRunner::run(FiltersFormatPossiblyUnsafeHtmlAllowListTest::class);
