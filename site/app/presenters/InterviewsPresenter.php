<?php
/**
 * Interviews presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class InterviewsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Interviews */
	protected $interviews;

	/** @var \MichalSpacekCz\Embed */
	protected $embed;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Interviews $interviews
	 * @param \MichalSpacekCz\Embed $embed
	 */
	public function __construct(
		MichalSpacekCz\Formatter\Texy $texyFormatter,
		MichalSpacekCz\Interviews $interviews,
		MichalSpacekCz\Embed $embed
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->interviews = $interviews;
		$this->embed = $embed;
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.interviews');
		$this->template->interviews = $this->interviews->getAll();
	}


	public function actionInterview($name)
	{
		$interview = $this->interviews->get($name);
		if (!$interview) {
			throw new Nette\Application\BadRequestException("I haven't been interviewed by {$name}, yet", Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.interview', [$interview->title]);
		$this->template->pageHeader = $interview->title;
		$this->template->description = $interview->description;
		$this->template->href = $interview->href;
		$this->template->date = $interview->date;
		$this->template->audioHref = $interview->audioHref;
		$this->template->videoHref = $interview->videoHref;
		$this->template->sourceName = $interview->sourceName;
		$this->template->sourceHref = $interview->sourceHref;
		foreach ($this->embed->getVideoTemplateVars($interview->videoHref, $interview->videoEmbed) as $key => $value) {
			$this->template->$key = $value;
		}
	}

}
