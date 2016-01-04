<?php
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

	/** @var string */
	private $talkAction;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Talks $talks
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Talks $talks
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->talks = $talks;
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.talks');
		$this->template->upcomingTalks = $this->talks->getUpcoming();
		$this->template->talks = $this->talks->getAll();
	}


	public function actionTalk($param)
	{
		$this->talkAction = $param;

		$talk = $this->talks->get($this->talkAction);
		if (!$talk) {
			throw new \Nette\Application\BadRequestException("I haven't talked about {$this->talkAction}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.talk', [strip_tags($talk->title), $talk->event]);
		$this->template->talkAction = $this->talkAction;
	}


	public function actionSlides($param)
	{
		$this->talkAction = $param;

		$talk = $this->talks->get($this->talkAction);
		if (!$talk) {
			throw new \Nette\Application\BadRequestException("I haven't talked about {$this->talkAction}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.admin.talkslides', [strip_tags($talk->title), $talk->event]);
		$this->template->talkTitle = $talk->title;
		$this->template->slides = $this->talks->getSlides($this->talkAction);
		$this->template->talkAction = $this->talkAction;
	}


	protected function createComponentEditTalk($formName)
	{
		$form = new \MichalSpacekCz\Form\Talk($this, $formName, $this->talkAction, $this->talks);
		$form->setTalk($this->talks->get($this->talkAction));
		$form->onSuccess[] = $this->submittedEditTalk;
		return $form;
	}


	public function submittedEditTalk(\MichalSpacekCz\Form\Talk $form)
	{
		$values = $form->getValues();
		$this->talks->update(
			$this->talkAction,
			$values->action,
			$values->title,
			$values->description,
			$values->date,
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


	protected function createComponentAddTalk($formName)
	{
		$form = new \MichalSpacekCz\Form\Talk($this, $formName, null, $this->talks);
		$form->onSuccess[] = $this->submittedAddTalk;
		return $form;
	}


	public function submittedAddTalk(\MichalSpacekCz\Form\Talk $form)
	{
		$values = $form->getValues();
		$this->talks->add(
			$values->action,
			$values->title,
			$values->description,
			$values->date,
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


	protected function createComponentAddSlide($formName)
	{
		$form = new \MichalSpacekCz\Form\TalkSlide($this, $formName);
		$form->onSuccess[] = $this->submittedAddSlide;
		return $form;
	}


	public function submittedAddSlide(\MichalSpacekCz\Form\TalkSlide $form)
	{
		$values = $form->getValues();
		try {
			$this->talks->addSlide($this->talkAction, $values->alias, $values->number);
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.slideadded'));
		} catch (\UnexpectedValueException $e) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.duplicatealias', [$e->getCode()]), 'error');
		}
		$this->redirect('Talks:slides', $this->talkAction);
	}

}
