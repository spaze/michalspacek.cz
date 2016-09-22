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

	/** @var \Nette\Database\Row */
	private $talk;


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
		$this->talk = $this->talks->getById($param);
		if (!$this->talk) {
			throw new \Nette\Application\BadRequestException("I haven't talked about id {$param}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.talk', [strip_tags($this->talk->title), $this->talk->event]);
		$this->template->talk = $this->talk;
	}


	public function actionSlides($param)
	{
		$this->talk = $this->talks->getById($param);
		if (!$this->talk) {
			throw new \Nette\Application\BadRequestException("I haven't talked about id {$param}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.admin.talkslides', [strip_tags($this->talk->title), $this->talk->event]);
		$this->template->talkTitle = $this->talk->title;
		$this->template->slides = $this->talks->getSlides($this->talk->talkId);
		$this->template->talk = $this->talk;
	}


	protected function createComponentEditTalk($formName)
	{
		$form = new \MichalSpacekCz\Form\Talk($this, $formName, $this->talk->action, $this->talks);
		$form->setTalk($this->talks->getById($this->talk->talkId));
		$form->onSuccess[] = $this->submittedEditTalk;
		return $form;
	}


	public function submittedEditTalk(\MichalSpacekCz\Form\Talk $form)
	{
		$values = $form->getValues();
		$this->talks->update(
			$this->talk->talkId,
			$values->action,
			$values->title,
			$values->description,
			$values->date,
			$values->duration,
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
			$values->duration,
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
			$this->talks->addSlide($this->talk->talkId, $values->alias, $values->number);
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.slideadded'));
		} catch (\UnexpectedValueException $e) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.duplicatealias', [$e->getCode()]), 'error');
		}
		$this->redirect('Talks:slides', $this->talk->talkId);
	}

}
