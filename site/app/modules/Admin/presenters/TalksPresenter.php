<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use MichalSpacekCz\Embed;
use MichalSpacekCz\Form\Talk;
use MichalSpacekCz\Form\TalkSlides;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Talks;
use Nette\Application\BadRequestException;
use Nette\Application\LinkGenerator;
use Nette\Database\Row;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use RuntimeException;
use UnexpectedValueException;

class TalksPresenter extends BasePresenter
{

	/** @var Texy */
	protected $texyFormatter;

	/** @var Talks */
	protected $talks;

	/** @var Row */
	private $talk;

	/** @var Row[] */
	private $slides;

	/** @var Embed */
	protected $embed;

	/** @var LinkGenerator */
	protected $linkGenerator;

	/** @var integer */
	private $newCount;

	/** @var integer */
	private $maxSlideUploads;


	public function __construct(Texy $texyFormatter, Talks $talks, LinkGenerator $linkGenerator, Embed $embed)
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
		} catch (RuntimeException $e) {
			throw new BadRequestException($e->getMessage());
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $this->talk);
		$this->template->talk = $this->talk;
	}


	public function actionSlides(string $param): void
	{
		try {
			$this->talk = $this->talks->getById((int)$param);
			$this->slides = $this->talks->getSlides($this->talk->talkId);
		} catch (RuntimeException $e) {
			throw new BadRequestException($e->getMessage());
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


	protected function createComponentEditTalk(string $formName): Talk
	{
		$form = new Talk($this, $formName, (string)$this->talk->action, $this->talks);
		$form->setTalk($this->talk);
		$form->onSuccess[] = [$this, 'submittedEditTalk'];
		return $form;
	}


	public function submittedEditTalk(Talk $form, ArrayHash $values): void
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


	protected function createComponentAddTalk(string $formName): Talk
	{
		$form = new Talk($this, $formName, null, $this->talks);
		$form->onSuccess[] = [$this, 'submittedAddTalk'];
		return $form;
	}


	public function submittedAddTalk(Talk $form, ArrayHash $values): void
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


	protected function createComponentSlides(string $formName): TalkSlides
	{
		$form = new TalkSlides($this, $formName, $this->slides, $this->newCount, $this->talks);
		$form->onSuccess[] = [$this, 'submittedSlides'];
		$form->onValidate[] = [$this, 'validateSlides'];
		return $form;
	}


	public function submittedSlides(TalkSlides $form, ArrayHash $values): void
	{
		try {
			$this->talks->saveSlides($this->talk->talkId, $this->slides, $values);
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.slideadded'));
		} catch (UnexpectedValueException $e) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.admin.duplicatealias', [$e->getCode()]), 'error');
		}
		$this->redirect('Talks:slides', $this->talk->talkId);
	}


	public function validateSlides(TalkSlides $form): void
	{
		// Check whether max allowed file uploads has been reached
		$uploaded = 0;
		$files = $this->request->getFiles();
		array_walk_recursive($files, function ($item) use (&$uploaded) {
			if ($item instanceof FileUpload) {
				$uploaded++;
			}
		});
		// If there's no error yet then the number of uploaded just coincidentally matches max allowed
		if ($form->hasErrors() && $uploaded >= $this->maxSlideUploads) {
			$form->addError($this->texyFormatter->translate('messages.talks.admin.maxslideuploadsexceeded', [(string)$this->maxSlideUploads]));
		}
	}

}
