<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Talk;

use MichalSpacekCz\Talks\Slides\TalkSlide;
use MichalSpacekCz\Talks\Slides\TalkSlideCollection;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Request;
use Nette\Http\FileUpload;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../bootstrap.php';

/** @testCase */
final class TalkSlidesFormFactoryTest extends TestCase
{

	public function __construct(
		private readonly TalkSlidesFormFactory $talkSlidesFormFactory,
		private readonly ApplicationPresenter $applicationPresenter,
	) {
	}


	public function testCreateOnSuccess(): void
	{
		$talkId = 123;
		$onSuccessMessage = $onSuccessType = $onSuccessTalkId = $result = null;
		$slides = new TalkSlideCollection($talkId);
		$slides->add(new TalkSlide(1, 'slide1', 1, 'slide1.jpg', 'slide-alt.jpg', null, 'Title 1', Html::fromText('Notes 1'), 'Notes 1', null, null, null));
		$form = $this->talkSlidesFormFactory->create(
			function (Html $message, string $type, int $talkId) use (&$onSuccessMessage, &$onSuccessType, &$onSuccessTalkId): void {
				$onSuccessMessage = $message->render();
				$onSuccessType = $type;
				$onSuccessTalkId = $talkId;
			},
			$talkId,
			$slides,
			0,
			new Request('foo'),
		);
		$this->applicationPresenter->anchorForm($form);
		Assert::noError(function () use (&$result, $form): void {
			$result = Arrays::invoke($form->onSuccess, $form);
		});
		Assert::same([null], $result);
		Assert::same('messages.talks.admin.slideadded', $onSuccessMessage);
		Assert::same('info', $onSuccessType);
		Assert::same($talkId, $onSuccessTalkId);
	}


	/**
	 * @return list<array{0:int, 1:string|null}>
	 */
	public function getMaxFiles(): array
	{
		return [
			[$this->talkSlidesFormFactory->getMaxSlideUploads(), null],
			[$this->talkSlidesFormFactory->getMaxSlideUploads() + 1, 'messages.talks.admin.maxslideuploadsexceeded'],
		];
	}


	/**
	 * @dataProvider getMaxFiles
	 */
	public function testCreateOnValidate(int $maxFiles, ?string $errorMessage): void
	{
		$files = [];
		for ($i = 0; $i < $maxFiles; $i++) {
			$files[] = new FileUpload([
				'name' => 'test',
				'size' => 123,
				'tmp_name' => 'test.temp',
				'error' => UPLOAD_ERR_OK,
			]);
		}
		// FileUploads with UPLOAD_ERR_NO_FILE are not counted as uploaded files
		$files[] = new FileUpload([
			'name' => 'no.file',
			'size' => 123,
			'tmp_name' => 'no.file.temp',
			'error' => UPLOAD_ERR_NO_FILE,
		]);
		$form = $this->talkSlidesFormFactory->create(
			function (): void {
			},
			303,
			new TalkSlideCollection(303),
			25,
			new Request('foo', files: $files),
		);
		$this->applicationPresenter->anchorForm($form);
		Arrays::invoke($form->onValidate, $form);
		if ($errorMessage === null) {
			Assert::count(0, $form->getErrors());
		} else {
			Assert::count(1, $form->getErrors());
			$error = $form->getErrors()[0];
			assert($error instanceof Html);
			Assert::same($errorMessage, $error->render());
		}
	}

}

TestCaseRunner::run(TalkSlidesFormFactoryTest::class);
