<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use MichalSpacekCz\Form\InterviewFormFactory;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Interviews\Exceptions\InterviewDoesNotExistException;
use MichalSpacekCz\Interviews\Interviews;
use MichalSpacekCz\Media\VideoThumbnails;
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
		private readonly VideoThumbnails $videoThumbnails,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.interviews');
		$this->template->interviews = $this->interviews->getAll();
		$this->template->videoThumbnailWidth = $this->videoThumbnails->getWidth();
		$this->template->videoThumbnailHeight = $this->videoThumbnails->getHeight();
	}


	public function actionInterview(int $param): void
	{
		try {
			$this->interview = $this->interviews->getById($param);
		} catch (InterviewDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.interview', [strip_tags($this->interview->title)]);
		$this->template->interview = $this->interview;
		$this->template->videoThumbnailWidth = $this->videoThumbnails->getWidth();
		$this->template->videoThumbnailHeight = $this->videoThumbnails->getHeight();
	}


	protected function createComponentEditInterview(): Form
	{
		return $this->interviewFormFactory->create(
			function (): never {
				$this->flashMessage('Rozhovor upraven');
				$this->redirect('Interviews:');
			},
			$this->interview,
		);
	}


	protected function createComponentAddInterview(): Form
	{
		return $this->interviewFormFactory->create(
			function (): never {
				$this->flashMessage('Rozhovor přidán');
				$this->redirect('Interviews:');
			},
		);
	}

}
