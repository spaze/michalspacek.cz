<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\DateTime\DateTimeFormat;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class BlogPostEditsTest extends TestCase
{

	public function __construct(
		private readonly BlogPostEdits $blogPostEdits,
		private readonly Database $database,
	) {
	}


	public function testGetEdits(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'editedAt' => new DateTime('2020-05-06 07:08:09'),
				'editedAtTimezone' => 'Europe/Tallinn',
				'summaryTexy' => '**Summary**',
			],
		]);
		$edits = $this->blogPostEdits->getEdits(123);
		Assert::count(1, $edits);
		Assert::same('2020-05-06T08:08:09.000000+03:00', $edits[0]->getEditedAt()->format(DateTimeFormat::RFC3339_MICROSECONDS));
		Assert::same('**Summary**', $edits[0]->getSummaryTexy());
		Assert::same('<strong>Summary</strong>', $edits[0]->getSummary()->toHtml());
	}

}

TestCaseRunner::run(BlogPostEditsTest::class);
