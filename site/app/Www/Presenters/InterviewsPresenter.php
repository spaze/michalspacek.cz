<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Embed;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Interviews;
use Nette\Application\BadRequestException;

class InterviewsPresenter extends BasePresenter
{

	/** @var Texy */
	protected $texyFormatter;

	/** @var Interviews */
	protected $interviews;

	/** @var Embed */
	protected $embed;


	public function __construct(
		Texy $texyFormatter,
		Interviews $interviews,
		Embed $embed
	) {
		$this->texyFormatter = $texyFormatter;
		$this->interviews = $interviews;
		$this->embed = $embed;
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.interviews');
		$this->template->interviews = $this->interviews->getAll();
	}


	public function actionInterview(string $name): void
	{
		$interview = $this->interviews->get($name);
		if (!$interview) {
			throw new BadRequestException("I haven't been interviewed by {$name}, yet");
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.interview', [$interview->title]);
		$this->template->pageHeader = $interview->title;
		$this->template->description = $interview->description;
		$this->template->href = $interview->href;
		$this->template->date = $interview->date;
		$this->template->audioHref = $interview->audioHref;
		$this->template->sourceName = $interview->sourceName;
		$this->template->sourceHref = $interview->sourceHref;

		$this->template->videoHref = $interview->videoHref;
		foreach ($this->embed->getVideoTemplateVars($interview) as $key => $value) {
			$this->template->$key = $value;
		}
	}

}
