<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

use DateTime;
use MichalSpacekCz\Media\Video;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Html;
use Override;
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


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testGet(): void
	{
		$this->database->setFetchPairsDefaultResult(['cs_CZ' => 'foobar']);
		Assert::same([], $this->talkLocaleUrls->getLinkParams($this->buildTalk(null)));
		Assert::same(['cs_CZ' => ['name' => 'foobar']], $this->talkLocaleUrls->getLinkParams($this->buildTalk(1337)));
	}


	public function testGetAlternateLinkParams(): void
	{
		$this->database->setFetchPairsDefaultResult(['cs_CZ' => 'foobar']);
		Assert::same([], $this->talkLocaleUrls->getAlternateLinkParams($this->buildTalk(null)));
		Assert::same(['cs_CZ' => ['name' => 'foobar']], $this->talkLocaleUrls->getAlternateLinkParams($this->buildTalk(1337)));
	}


	/**
	 * The talk names the one whose slides it shows as canonical, so it has no alternates, even though it's translated.
	 */
	public function testGetAlternateLinkParamsShowsOtherTalksSlides(): void
	{
		$this->database->setFetchPairsDefaultResult(['cs_CZ' => 'foobar', 'en_US' => 'waldo']);
		Assert::same([], $this->talkLocaleUrls->getAlternateLinkParams($this->buildTalk(1337, 303)));
	}


	private function buildTalk(?int $translationGroup, ?int $slidesTalkId = null): Talk
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
			Html::text('title'),
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
			Html::text('event'),
			'event',
			null,
			null,
			null,
			null,
			null,
			$slidesTalkId,
			null,
			null,
			null,
			null,
			false,
		);
	}

}

TestCaseRunner::run(TalkLocaleUrlsTest::class);
