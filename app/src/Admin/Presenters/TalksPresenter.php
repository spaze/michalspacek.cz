<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Form\TalkSlidesFormFactory;
use MichalSpacekCz\Form\UiForm;
use MichalSpacekCz\Http\HttpInput;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\ShouldNotHappenException;
use MichalSpacekCz\Talks\Exceptions\TalkDoesNotExistException;
use MichalSpacekCz\Talks\Slides\TalkSlideCollection;
use MichalSpacekCz\Talks\Slides\TalkSlides;
use MichalSpacekCz\Talks\Talk;
use MichalSpacekCz\Talks\TalkInputs;
use MichalSpacekCz\Talks\TalkInputsFactory;
use MichalSpacekCz\Talks\Talks;
use Nette\Application\BadRequestException;
use Nette\Utils\Html;

final class TalksPresenter extends BasePresenter
{

	private ?Talk $talk = null;

	private ?TalkSlideCollection $slides = null;

	private int $newCount = 0;


	public function __construct(
		private readonly Talks $talks,
		private readonly TalkSlides $talkSlides,
		private readonly TalkInputsFactory $talkInputsFactory,
		private readonly TalkSlidesFormFactory $talkSlidesFormFactory,
		private readonly HttpInput $httpInput,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.talks');
		$this->template->defaultLocale = $this->translator->getDefaultLocale();
		$this->template->upcomingTalks = $this->talks->getUpcoming();
		$this->template->talks = $this->talks->getAll();
	}


	public function actionTalk(int $param): void
	{
		try {
			$this->talk = $this->talks->getById($param);
		} catch (TalkDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $this->talk);
		$this->template->talk = $this->talk;
	}


	public function actionSlides(int $param): void
	{
		try {
			$this->talk = $this->talks->getById($param);
			$this->slides = $this->talkSlides->getSlides($this->talk);
		} catch (ContentTypeException | TalkDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.admin.talkslides', $this->talk);
		$this->template->talkTitle = $this->talk->getTitle();
		$this->template->slides = $this->slides;
		$this->template->talk = $this->talk;
		$this->template->maxSlideUploads = $this->talkSlidesFormFactory->getMaxSlideUploads();
		$new = $this->httpInput->getPostArray('new');
		$this->template->newCount = $this->newCount = $new !== null ? count($new) : (int)(count($this->slides) === 0);
		$this->template->dimensions = $this->talkSlides->getSlideDimensions();
	}


	protected function createComponentEditTalkInputs(): TalkInputs
	{
		if ($this->talk === null) {
			throw new ShouldNotHappenException('actionTalk() will be called first');
		}
		return $this->talkInputsFactory->createFor($this->talk);
	}


	protected function createComponentAddTalkInputs(): TalkInputs
	{
		return $this->talkInputsFactory->create();
	}


	protected function createComponentSlides(): UiForm
	{
		$request = $this->getRequest();
		if ($this->talk === null || $this->slides === null || $request === null) {
			throw new ShouldNotHappenException('actionSlides() will be called first');
		}
		$talkId = $this->talk->getId();
		return $this->talkSlidesFormFactory->create(
			function (Html $message, string $type, int $talkId): never {
				$this->flashMessage($message, $type);
				$this->redirect('Talks:slides', $talkId);
			},
			$talkId,
			$this->slides,
			$this->newCount,
			$request,
		);
	}

}
