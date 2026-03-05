<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace Presentation\Www\Talks;

use DateTime;
use MichalSpacekCz\Presentation\Www\Talks\TalksDefaultTemplateParametersFactory;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\Talks\TalkTestDataFactory;
use MichalSpacekCz\Test\TestCaseRunner;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/** @testCase */
final class TalksDefaultTemplateParametersFactoryTest extends TestCase
{

	public function __construct(
		private readonly TalksDefaultTemplateParametersFactory $templateParametersFactory,
		private readonly TalkTestDataFactory $testDataFactory,
		private readonly Database $database,
	) {
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCreate(): void
	{
		// Talks
		$this->database->addFetchAllResult([
			$this->testDataFactory->getDatabaseResultData(id: 11, date: new DateTime('2025-12-02 00:00:00')),
			$this->testDataFactory->getDatabaseResultData(id: 12, date: new DateTime('2025-12-03 00:00:00')),
			$this->testDataFactory->getDatabaseResultData(id: 13, date: new DateTime('2026-01-04 00:00:00')),
		]);
		// Favorites
		$this->database->addFetchAllResult([
			[
				'action' => 'https://action.example',
				'title' => 'Title',
				'favorite' => 'Favorite "%s":[%s]',
			],
		]);
		// Upcoming talks
		$this->database->addFetchAllResult([
			$this->testDataFactory->getDatabaseResultData(id: 111, date: new DateTime('2025-12-02 00:00:00')),
		]);

		$parameters = $this->templateParametersFactory->create();
		Assert::same('messages.title.talks', $parameters->pageTitle);
		Assert::same('Favorite <a href="https://action.example">Title</a>', $parameters->favoriteTalks[0]->render());
		Assert::same(111, $parameters->upcomingTalks[0]->getId());

		$talkIds = [];
		foreach ($parameters->talks as $talks) {
			foreach ($talks as $talk) {
				$talkIds[$talk->getDate()->format('Y')][] = $talk->getId();
			}
		}
		Assert::same([2025 => [11, 12], 2026 => [13]], $talkIds);
	}

}

TestCaseRunner::run(TalksDefaultTemplateParametersFactoryTest::class);
