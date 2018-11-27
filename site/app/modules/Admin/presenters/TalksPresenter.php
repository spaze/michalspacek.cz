<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use \Nette\Utils\Html;

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

	/** @var \Nette\Database\Row[] */
	private $slides;

	/** @var \MichalSpacekCz\Embed */
	protected $embed;

	/** @var \Nette\Application\LinkGenerator */
	protected $linkGenerator;

	/** @var integer */
	private $newCount;

	/** @var integer */
	private $maxSlideUploads;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Talks $talks
	 * @param \Nette\Application\LinkGenerator $linkGenerator
	 * @param \MichalSpacekCz\Embed $embed
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Talks $talks,
		\Nette\Application\LinkGenerator $linkGenerator,
		\MichalSpacekCz\Embed $embed
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->talks = $talks;
		$this->linkGenerator = $linkGenerator;
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
			throw new \Nette\Application\BadRequestException($e->getMessage(), \Nette\Http\IResponse::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $this->talk);
		$this->template->talk = $this->talk;
	}


	public function actionSlides(string $param): void
	{
		try {
			$this->talk = $this->talks->getById((int)$param);
			$this->slides = $this->talks->getSlides($this->talk->talkId);
		} catch (\RuntimeException $e) {
			throw new \Nette\Application\BadRequestException($e->getMessage(), \Nette\Http\IResponse::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.admin.talkslides', $this->talk);
		$this->template->talkTitle = $this->talk->title;
		$this->template->slides = $this->slides;
		$this->template->talk = $this->talk;
		foreach ($this->embed->getSlidesTemplateVars($this->talk) as $key => $value) {
			$this->template->$key = $value;
		}
		$this->template->maxSlideUploads = $this->maxSlideUploads = (int)ini_get('max_file_uploads');
		$count = (is_array($this->request->getPost('new')) ? count($this->request->getPost('new')) : 0);
		$this->template->newCount = $this->newCount = ($count ?: (int)empty($this->slides));
		$this->template->dimensions = $this->talks->getSlideDimensions();
	}


	protected function createComponentEditTalk(string $formName)
	{
		$form = new \MichalSpacekCz\Form\Talk($this, $formName, (string)$this->talk->action, $this->talks);
		$form->setTalk($this->talk);
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
			$values->slidesTalk,
			$values->slidesHref,
			$values->slidesEmbed,
			$values->videoHref,
			$values->videoEmbed,
			$values->event,
			$values->eventHref,
			$values->ogImage,
			$values->transcript,
			$values->favorite,
			$values->supersededBy,
			$values->publishSlides
		);
		$this->flashMessage(
			Html::el()
				->setText('Přednáška upravena ' )
				->addHtml(Html::el('a')->href($this->linkGenerator->link('Www:Talks:talk', [$values->action]))->setText('Zobrazit'))
		);
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
			$values->slidesTalk,
			$values->slidesHref,
			$values->slidesEmbed,
			$values->videoHref,
			$values->videoEmbed,
			$values->event,
			$values->eventHref,
			$values->ogImage,
			$values->transcript,
			$values->favorite,
			$values->supersededBy,
			$values->publishSlides
		);
		$this->flashMessage('Přednáška přidána');
		$this->redirect('Talks:');
	}


	protected function createComponentSlides(string $formName)
	{
		$form = new \MichalSpacekCz\Form\TalkSlides($this, $formName, $this->slides, $this->newCount, $this->talks);
		$form->onSuccess[] = [$this, 'submittedSlides'];
		$form->onValidate[] = [$this, 'validateSlides'];
		return $form;
	}


	public function submittedSlides(\MichalSpacekCz\Form\TalkSlides $form, \Nette\Utils\ArrayHash $values): void
	{
		try {
			$this->talks->saveSlides($this->talk->talkId, $this->slides, $values);
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.slideadded'));
		} catch (\UnexpectedValueException $e) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.duplicatealias', [$e->getCode()]), 'error');
		}
		$this->redirect('Talks:slides', $this->talk->talkId);
	}


	public function validateSlides(\MichalSpacekCz\Form\TalkSlides $form)
	{
		// Check whether max allowed file uploads has been reached
		$uploaded = 0;
		$files = $this->request->getFiles();
		array_walk_recursive($files, function ($item) use (&$uploaded) {
			if ($item instanceof \Nette\Http\FileUpload) {
				$uploaded++;
			}
		});
		// If there's no error yet then the number of uploaded just coincidentally matches max allowed
		if ($form->hasErrors() && $uploaded >= $this->maxSlideUploads) {
			$form->addError($this->texyFormatter->translate('messages.talks.admin.maxslideuploadsexceeded', [$this->maxSlideUploads]));
		}
	}


}
