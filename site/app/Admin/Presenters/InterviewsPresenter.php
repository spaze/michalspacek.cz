<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\InterviewFormFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Interviews\Interviews;
use Nette\Application\BadRequestException;
use Nette\Database\Row;
use Nette\Forms\Form;

class InterviewsPresenter extends BasePresenter
{

	/** @var Row<mixed> */
	private Row $interview;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly Interviews $interviews,
		private readonly InterviewFormFactory $interviewFormFactory,
	) {
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


	protected function createComponentEditInterview(string $formName): Form
	{
		return $this->interviewFormFactory->create(
			function (): never {
				$this->flashMessage('Rozhovor upraven');
				$this->redirect('Interviews:');
			},
			$this->interview,
		);
	}


	protected function createComponentAddInterview(string $formName): Form
	{
		return $this->interviewFormFactory->create(
			function (): never {
				$this->flashMessage('Rozhovor přidán');
				$this->redirect('Interviews:');
			},
		);
	}

}
