<?php
declare(strict_types = 1);

namespace App\AdminModule\Presenters;

use MichalSpacekCz\Form\Interview;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Interviews;
use Nette\Application\BadRequestException;
use Nette\Database\Row;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;

class InterviewsPresenter extends BasePresenter
{

	/** @var Texy */
	protected $texyFormatter;

	/** @var Interviews */
	protected $interviews;

	/** @var Row */
	private $interview;


	public function __construct(Texy $texyFormatter, Interviews $interviews)
	{
		$this->texyFormatter = $texyFormatter;
		$this->interviews = $interviews;
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.interviews');
		$this->template->interviews = $this->interviews->getAll();
	}


	public function actionInterview(int $param): void
	{
		$this->interview = $this->interviews->getById($param);
		if (!$this->interview) {
			throw new BadRequestException("Interview id {$param} does not exist, yet");
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.interview', [strip_tags($this->interview->title)]);
		$this->template->interview = $this->interview;
	}


	protected function createComponentEditInterview(string $formName): Interview
	{
		$form = new Interview($this, $formName);
		$form->setInterview($this->interview);
		$form->onSuccess[] = [$this, 'submittedEditInterview'];
		return $form;
	}


	public function submittedEditInterview(Form $form, ArrayHash $values): void
	{
		$this->interviews->update(
			$this->interview->interviewId,
			$values->action,
			$values->title,
			$values->description,
			$values->date,
			$values->href,
			$values->audioHref,
			$values->audioEmbed,
			$values->videoHref,
			$values->videoEmbed,
			$values->sourceName,
			$values->sourceHref
		);
		$this->flashMessage('Rozhovor upraven');
		$this->redirect('Interviews:');
	}


	protected function createComponentAddInterview(string $formName): Interview
	{
		$form = new Interview($this, $formName);
		$form->onSuccess[] = [$this, 'submittedAddInterview'];
		return $form;
	}


	public function submittedAddInterview(Form $form, ArrayHash $values): void
	{
		$this->interviews->add(
			$values->action,
			$values->title,
			$values->description,
			$values->date,
			$values->href,
			$values->audioHref,
			$values->audioEmbed,
			$values->videoHref,
			$values->videoEmbed,
			$values->sourceName,
			$values->sourceHref
		);
		$this->flashMessage('Rozhovor přidán');
		$this->redirect('Interviews:');
	}

}
