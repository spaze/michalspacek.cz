<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Admin\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Interviews\Exceptions\InterviewDoesNotExistException;
use MichalSpacekCz\Interviews\Interview;
use MichalSpacekCz\Interviews\InterviewInputs;
use MichalSpacekCz\Interviews\InterviewInputsFactory;
use MichalSpacekCz\Interviews\Interviews;
use MichalSpacekCz\ShouldNotHappenException;
use Nette\Application\BadRequestException;

final class InterviewsPresenter extends BasePresenter
{

	private ?Interview $interview = null;


	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly Translator $translator,
		private readonly Interviews $interviews,
		private readonly InterviewInputsFactory $interviewInputsFactory,
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
		try {
			$this->interview = $this->interviews->getById($param);
		} catch (InterviewDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.interview', [strip_tags($this->interview->getTitle())]);
	}


	protected function createComponentEditInterviewInputs(): InterviewInputs
	{
		if ($this->interview === null) {
			throw new ShouldNotHappenException('actionInterview() will be called first');
		}
		return $this->interviewInputsFactory->createFor($this->interview);
	}


	protected function createComponentAddInterviewInputs(): InterviewInputs
	{
		return $this->interviewInputsFactory->create();
	}

}
