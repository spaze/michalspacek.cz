<?php
/** @noinspection PhpUnhandledExceptionInspection */
declare(strict_types = 1);

namespace MichalSpacekCz\Form;

use MichalSpacekCz\Talks\Slides\TalkSlide;
use MichalSpacekCz\Talks\Slides\TalkSlideCollection;
use MichalSpacekCz\Test\Application\ApplicationPresenter;
use MichalSpacekCz\Test\PrivateProperty;
use MichalSpacekCz\Test\TestCaseRunner;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Utils\Arrays;
use Nette\Utils\Html;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../bootstrap.php';

/** @testCase */
class TalkSlidesFormFactoryTest extends TestCase
{

	public function __construct(
		private readonly TalkSlidesFormFactory $talkSlidesFormFactory,
		private readonly Application $application,
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
		$presenter = $this->applicationPresenter->createUiPresenter('Admin:Talks', 'foo', 'slides');
		PrivateProperty::setValue($this->application, 'presenter', $presenter);
		/** @noinspection PhpInternalEntityUsedInspection */
		$form->setParent($presenter);
		Assert::noError(function () use (&$result, $form): void {
			$result = Arrays::invoke($form->onSuccess, $form);
		});
		Assert::same([null], $result);
		Assert::same('messages.talks.admin.slideadded', $onSuccessMessage);
		Assert::same('info', $onSuccessType);
		Assert::same($talkId, $onSuccessTalkId);
	}

}

TestCaseRunner::run(TalkSlidesFormFactoryTest::class);
