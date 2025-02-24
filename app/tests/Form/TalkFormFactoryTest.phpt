<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Override;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
final class TalkFormFactoryTest extends TestCase
{

	private ?Html $message = null;


	public function __construct(
		private readonly Database $database,
		private readonly TalkFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	#[Override]
	protected function setUp(): void
	{
		$this->database->addFetchPairsResult([
			123 => 'cs_CZ',
			321 => 'en_US',
		]);
	}


	#[Override]
	protected function tearDown(): void
	{
		$this->database->reset();
	}


	public function testCreateOnSuccessAdd(): void
	{
		$form = $this->formFactory->create(
			function (Html $message): void {
				$this->message = $message;
			},
			null,
		);
		$form->setDefaults([
			'locale' => 123,
			'action' => 'foo',
			'date' => '3210-09-08 10:20:30',
		]);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onSuccess, $form);
		Assert::same('Přednáška přidána <a href="https://www.rizek.test/prednasky/foo">Zobrazit</a>', $this->message?->toHtml());
		Assert::same([
			[
				'key_locale' => 123,
				'key_translation_group' => null,
				'action' => 'foo',
				'title' => '',
				'description' => null,
				'date' => '3210-09-08 10:20:30',
				'duration' => null,
				'href' => null,
				'key_talk_slides' => null,
				'key_talk_filenames' => null,
				'slides_href' => null,
				'slides_embed' => null,
				'slides_note' => null,
				'video_href' => null,
				'video_thumbnail' => null,
				'video_thumbnail_alternative' => null,
				'video_embed' => null,
				'event' => '',
				'event_href' => null,
				'og_image' => null,
				'transcript' => null,
				'favorite' => null,
				'key_superseded_by' => null,
				'publish_slides' => false,
			],
		], $this->database->getParamsArrayForQuery('INSERT INTO talks'));
	}

}

TestCaseRunner::run(TalkFormFactoryTest::class);
