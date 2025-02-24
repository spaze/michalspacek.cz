<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use DateTime;
use MichalSpacekCz\Media\Video;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TalkLocaleUrlsTest extends TestCase
{

	public function __construct(
		private readonly TalkLocaleUrls $talkLocaleUrls,
		private readonly Database $database,
	) {
	}


	public function testGet(): void
	{
		$expected = ['cs_CZ' => 'foobar'];
		$this->database->setFetchPairsDefaultResult($expected);
		Assert::same([], $this->talkLocaleUrls->get($this->buildTalk(null)));
		Assert::same($expected, $this->talkLocaleUrls->get($this->buildTalk(1337)));
	}


	private function buildTalk(?int $translationGroup): Talk
	{
		$video = new Video(
			null,
			null,
			null,
			null,
			null,
			null,
			320,
			200,
			null,
		);
		return new Talk(
			10,
			1,
			'cs_CZ',
			$translationGroup,
			null,
			null,
			Html::fromText('title'),
			'title',
			null,
			null,
			new DateTime(),
			null,
			null,
			false,
			null,
			null,
			null,
			null,
			$video,
			null,
			Html::fromText('event'),
			'event',
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			null,
			false,
		);
	}

}

TestCaseRunner::run(TalkLocaleUrlsTest::class);
