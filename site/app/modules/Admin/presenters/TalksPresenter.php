<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

/**
 * Talks presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TalksPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Talks */
	protected $talks;

	/** @var \Nette\Database\Row */
	private $talk;

	/** @var \MichalSpacekCz\Embed */
	protected $embed;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Talks $talks
	 * @param \MichalSpacekCz\Embed $embed
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Talks $talks,
		\MichalSpacekCz\Embed $embed
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->talks = $talks;
		$this->embed = $embed;
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.talks');
		$this->template->upcomingTalks = $this->talks->getUpcoming();
		$this->template->talks = $this->talks->getAll();
	}


	public function actionTalk(string $param): void
	{
		try {
			$this->talk = $this->talks->getById((int)$param);
		} catch (\RuntimeException $e) {
			throw new \Nette\Application\BadRequestException($e->getMessage(), \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $this->talk);
		$this->template->talk = $this->talk;
	}


	public function actionSlides(string $param): void
	{
		try {
			$this->talk = $this->talks->getById((int)$param);
		} catch (\RuntimeException $e) {
			throw new \Nette\Application\BadRequestException($e->getMessage(), \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.admin.talkslides', $this->talk);
		$this->template->talkTitle = $this->talk->title;
		$this->template->slides = $this->talks->getSlides($this->talk->talkId);
		$this->template->talk = $this->talk;
		foreach ($this->embed->getSlidesTemplateVars($this->talk) as $key => $value) {
			$this->template->$key = $value;
		}
	}


	protected function createComponentEditTalk(string $formName)
	{
		$form = new \MichalSpacekCz\Form\Talk($this, $formName, $this->talk->action, $this->talks);
		$form->setTalk($this->talks->getById($this->talk->talkId));
		$form->onSuccess[] = [$this, 'submittedEditTalk'];
		return $form;
	}


	public function submittedEditTalk(\MichalSpacekCz\Form\Talk $form, \Nette\Utils\ArrayHash $values): void
	{
		$this->talks->update(
			$this->talk->talkId,
			$values->action,
			$values->title,
			$values->description,
			$values->date,
			(int)$values->duration,
			$values->href,
			$values->origSlides,
			$values->slidesHref,
			$values->slidesEmbed,
			$values->videoHref,
			$values->videoEmbed,
			$values->event,
			$values->eventHref,
			$values->ogImage,
			$values->transcript,
			$values->favorite,
			$values->supersededBy
		);
		$this->flashMessage('Přednáška upravena');
		$this->redirect('Talks:');
	}


	protected function createComponentAddTalk(string $formName)
	{
		$form = new \MichalSpacekCz\Form\Talk($this, $formName, null, $this->talks);
		$form->onSuccess[] = [$this, 'submittedAddTalk'];
		return $form;
	}


	public function submittedAddTalk(\MichalSpacekCz\Form\Talk $form, \Nette\Utils\ArrayHash $values): void
	{
		$this->talks->add(
			$values->action,
			$values->title,
			$values->description,
			$values->date,
			(int)$values->duration,
			$values->href,
			$values->origSlides,
			$values->slidesHref,
			$values->slidesEmbed,
			$values->videoHref,
			$values->videoEmbed,
			$values->event,
			$values->eventHref,
			$values->ogImage,
			$values->transcript,
			$values->favorite,
			$values->supersededBy
		);
		$this->flashMessage('Přednáška přidána');
		$this->redirect('Talks:');
	}


	protected function createComponentAddSlide(string $formName)
	{
		$form = new \MichalSpacekCz\Form\TalkSlide($this, $formName);
		$form->onSuccess[] = [$this, 'submittedAddSlide'];
		return $form;
	}


	public function submittedAddSlide(\MichalSpacekCz\Form\TalkSlide $form, \Nette\Utils\ArrayHash $values): void
	{
		try {
			$this->talks->addSlide($this->talk->talkId, $values->alias, $values->number);
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.slideadded'));
		} catch (\UnexpectedValueException $e) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.duplicatealias', [$e->getCode()]), 'error');
		}
		$this->redirect('Talks:slides', $this->talk->talkId);
	}

}
