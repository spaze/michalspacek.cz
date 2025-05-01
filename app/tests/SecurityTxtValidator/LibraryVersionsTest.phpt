<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\SecurityTxtValidator;

use MichalSpacekCz\Application\DependencyVersion;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class LibraryVersionsTest extends TestCase
{

	public function __construct(
		private readonly Database $database,
		private readonly LibraryVersions $libraryVersions,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	/**
	 * The insert and the lookup are one statement, so two requests meeting a build not seen before cannot end up
	 * with two rows for it: the second one runs into the unique key and is handed the id the first one got.
	 */
	public function testGetIdWritesTheBuildOnceAndReturnsItsId(): void
	{
		$this->database->addInsertId('12');
		Assert::same(12, $this->libraryVersions->getId(new DependencyVersion('3.2.0', 'cafe1234')));
		$written = $this->database->getParamsArrayForQuery('INSERT INTO library_versions');
		Assert::count(1, $written);
		Assert::same('3.2.0', $written[0]['version']);
		Assert::same('cafe1234', $written[0]['reference']);
		assert(is_string($written[0]['first_seen']));
		Assert::match('~^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$~', $written[0]['first_seen']);
	}

}

TestCaseRunner::run(LibraryVersionsTest::class);
