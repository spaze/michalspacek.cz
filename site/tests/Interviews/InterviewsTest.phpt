<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Interviews;

use DateTime;
use MichalSpacekCz\Test\Database\Database;
use Tester\Assert;
use Tester\TestCase;

$runner = require __DIR__ . '/../bootstrap.php';

/** @testCase */
class InterviewsTest extends TestCase
{

	public function __construct(
		private readonly Interviews $interviews,
		private readonly Database $database,
	) {
	}


	public function testGetAll(): void
	{
		$this->database->setFetchAllDefaultResult([
			[
				'interviewId' => 1,
				'action' => 'action-1',
				'title' => 'Action 1',
				'description' => 'desc**rip**',
				'date' => new DateTime(),
				'href' => 'https://example.com',
				'audioHref' => null,
				'audioEmbed' => null,
				'videoHref' => null,
				'videoThumbnail' => null,
				'videoThumbnailAlternative' => null,
				'videoEmbed' => null,
				'sourceName' => null,
				'sourceHref' => null,
			],
			[
				'interviewId' => 2,
				'action' => 'action-2',
				'title' => 'Action 2',
				'description' => 'desc**trip**',
				'date' => new DateTime(),
				'href' => 'https://example.net',
				'audioHref' => 'https://audio.href.example',
				'audioEmbed' => 'https://audio.embed.example',
				'videoHref' => 'https://video.href.example',
				'videoThumbnail' => 'thumbnail.jpg',
				'videoThumbnailAlternative' => 'thumbnail.webp',
				'videoEmbed' => 'https://video.embed.example',
				'sourceName' => 'Sauce',
				'sourceHref' => 'https://source.href.example',
			],
		]);
		$interviews = $this->interviews->getAll();
		Assert::count(2, $interviews);
		Assert::same(1, $interviews[0]->getId());
		Assert::same("<p>desc<strong>rip</strong></p>\n", $interviews[0]->getDescription()?->render());
		Assert::same('desc**rip**', $interviews[0]->getDescriptionTexy());
		Assert::null($interviews[0]->getVideoThumbnail()->getVideoHref());
		Assert::null($interviews[0]->getVideoThumbnail()->getUrl());
		Assert::null($interviews[0]->getVideoThumbnail()->getAlternativeUrl());
		Assert::same(2, $interviews[1]->getId());
		Assert::same("<p>desc<strong>trip</strong></p>\n", $interviews[1]->getDescription()?->render());
		Assert::same('desc**trip**', $interviews[1]->getDescriptionTexy());
		Assert::same('https://video.href.example', $interviews[1]->getVideoThumbnail()->getVideoHref());
		Assert::same('https://www.domain.example/i/images/interviews/2/thumbnail.jpg', $interviews[1]->getVideoThumbnail()->getUrl());
		Assert::same('https://www.domain.example/i/images/interviews/2/thumbnail.webp', $interviews[1]->getVideoThumbnail()->getAlternativeUrl());
	}


	public function testGet(): void
	{
		$this->database->setFetchResult([
			'interviewId' => 1,
			'action' => 'action-1',
			'title' => 'Action 1',
			'description' => 'desc**rip**',
			'date' => new DateTime(),
			'href' => 'https://example.com',
			'audioHref' => null,
			'audioEmbed' => null,
			'videoHref' => null,
			'videoThumbnail' => null,
			'videoThumbnailAlternative' => null,
			'videoEmbed' => null,
			'sourceName' => null,
			'sourceHref' => null,
		]);
		$interview = $this->interviews->get('action-1');
		Assert::same(1, $interview->getId());
		Assert::same("<p>desc<strong>rip</strong></p>\n", $interview->getDescription()?->render());
		Assert::same('desc**rip**', $interview->getDescriptionTexy());
		Assert::null($interview->getVideoThumbnail()->getVideoHref());
		Assert::null($interview->getVideoThumbnail()->getUrl());
		Assert::null($interview->getVideoThumbnail()->getAlternativeUrl());
	}


	public function testGetById(): void
	{
		$this->database->setFetchResult([
			'interviewId' => 2,
			'action' => 'action-2',
			'title' => 'Action 2',
			'description' => 'desc**trip**',
			'date' => new DateTime(),
			'href' => 'https://example.net',
			'audioHref' => 'https://audio.href.example',
			'audioEmbed' => 'https://audio.embed.example',
			'videoHref' => 'https://video.href.example',
			'videoThumbnail' => 'thumbnail.jpg',
			'videoThumbnailAlternative' => 'thumbnail.webp',
			'videoEmbed' => 'https://video.embed.example',
			'sourceName' => 'Sauce',
			'sourceHref' => 'https://source.href.example',
		]);
		$interview = $this->interviews->getById(2);
		Assert::same(2, $interview->getId());
		Assert::same("<p>desc<strong>trip</strong></p>\n", $interview->getDescription()?->render());
		Assert::same('desc**trip**', $interview->getDescriptionTexy());
		Assert::same('https://video.href.example', $interview->getVideoThumbnail()->getVideoHref());
		Assert::same('https://www.domain.example/i/images/interviews/2/thumbnail.jpg', $interview->getVideoThumbnail()->getUrl());
		Assert::same('https://www.domain.example/i/images/interviews/2/thumbnail.webp', $interview->getVideoThumbnail()->getAlternativeUrl());
	}

}

$runner->run(InterviewsTest::class);
