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
				'id' => 1,
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
				'sourceName' => 'Source Name',
				'sourceHref' => 'https://source.example',
			],
			[
				'id' => 2,
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
		Assert::null($interviews[0]->getVideo()->getVideoHref());
		Assert::null($interviews[0]->getVideo()->getThumbnailUrl());
		Assert::null($interviews[0]->getVideo()->getThumbnailAlternativeUrl());
		Assert::same(2, $interviews[1]->getId());
		Assert::same("<p>desc<strong>trip</strong></p>\n", $interviews[1]->getDescription()?->render());
		Assert::same('desc**trip**', $interviews[1]->getDescriptionTexy());
		Assert::same('https://video.href.example', $interviews[1]->getVideo()->getVideoHref());
		Assert::same('https://www.domain.example/i/images/interviews/2/thumbnail.jpg', $interviews[1]->getVideo()->getThumbnailUrl());
		Assert::same('https://www.domain.example/i/images/interviews/2/thumbnail.webp', $interviews[1]->getVideo()->getThumbnailAlternativeUrl());
	}


	public function testGet(): void
	{
		$this->database->setFetchResult([
			'id' => 1,
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
			'sourceName' => 'Source Name',
			'sourceHref' => 'https://source.example',
		]);
		$interview = $this->interviews->get('action-1');
		Assert::same(1, $interview->getId());
		Assert::same("<p>desc<strong>rip</strong></p>\n", $interview->getDescription()?->render());
		Assert::same('desc**rip**', $interview->getDescriptionTexy());
		Assert::null($interview->getVideo()->getVideoHref());
		Assert::null($interview->getVideo()->getThumbnailUrl());
		Assert::null($interview->getVideo()->getThumbnailAlternativeUrl());
	}


	public function testGetById(): void
	{
		$this->database->setFetchResult([
			'id' => 2,
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
		Assert::same('https://video.href.example', $interview->getVideo()->getVideoHref());
		Assert::same('https://www.domain.example/i/images/interviews/2/thumbnail.jpg', $interview->getVideo()->getThumbnailUrl());
		Assert::same('https://www.domain.example/i/images/interviews/2/thumbnail.webp', $interview->getVideo()->getThumbnailAlternativeUrl());
	}

}

$runner->run(InterviewsTest::class);
