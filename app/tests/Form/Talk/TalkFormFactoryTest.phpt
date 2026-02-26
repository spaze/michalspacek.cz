<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Talk;

use Exception;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\Application\LocaleLinkGeneratorMock;
use MichalSpacekCz\Test\Database\Database;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Nette\Application\IPresenterFactory;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Override;
use Stringable;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TalkFormFactoryTest extends TestCase
{

	private ?Html $message = null;


	public function __construct(
		private readonly Database $database,
		private readonly TalkFormFactory $formFactory,
		private readonly ApplicationPresenter $applicationPresenter,
		private readonly LocaleLinkGeneratorMock $localeLinkGenerator,
		IPresenterFactory $presenterFactory,
		Application $application,
	) {
		$presenter = $presenterFactory->createPresenter('Www:Homepage'); // Has to be a real presenter that extends Ui\Presenter
		assert($presenter instanceof Presenter);
		PrivateProperty::setValue($application, 'presenter', $presenter);
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
		$this->localeLinkGenerator->reset();
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


	public function testValidate(): void
	{
		$texyFieldsValues = [
			'title' => '"foo":[link:invalid]',
			'description' => '"foo":[link:invalid]',
			'slidesNote' => '"foo":[link:invalid]',
			'event' => '"foo":[link:invalid]',
			'transcript' => '"foo":[link:invalid]',
		];
		$texyFields = array_keys($texyFieldsValues);
		$i = 0;
		// Each Texy field must throw an exception with a unique message to have them all in the errors array below
		$this->localeLinkGenerator->willThrow(function () use ($texyFields, &$i): Exception {
			return new InvalidLinkException("Texy {$texyFields[$i++]}");
		});

		$form = $this->formFactory->create(
			function (): void {
			},
		);
		$form->setDefaults($texyFieldsValues);
		$form->validate();

		$expected = [
			'This field is required.', // CSRF protection error message because the field is missing in the test
			'Zadejte prosím jazyk',
			'Invalid link: Texy title',
			'Invalid link: Texy description',
			'Zadejte datum',
			'Invalid link: Texy slidesNote',
			'Invalid link: Texy event',
			'Invalid link: Texy transcript',
		];
		$errors = [];
		foreach ($form->getErrors() as $error) {
			$errors[] = $error instanceof Stringable ? (string)$error : $error;
		}
		Assert::same($expected, $errors);
	}

}

TestCaseRunner::run(TalkFormFactoryTest::class);
