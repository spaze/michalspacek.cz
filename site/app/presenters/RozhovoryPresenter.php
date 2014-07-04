<?php
/**
 * Rozhovory presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class RozhovoryPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Interviews */
	protected $interviews;

	/** @var \MichalSpacekCz\Embed */
	protected $embed;


	/**
	 * @param \MichalSpacekCz\Interviews $interviews
	 * @param \MichalSpacekCz\Embed $embed
	 */
	public function __construct(\MichalSpacekCz\Interviews $interviews, \MichalSpacekCz\Embed $embed)
	{
		$this->interviews = $interviews;
		$this->embed = $embed;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle = 'Rozhovory';
		$this->template->interviews = $this->interviews->getAll();
	}


	public function actionRozhovor($name)
	{
		$interview = $this->interviews->get($name);
		if (!$interview) {
			throw new \Nette\Application\BadRequestException("I haven't been interviewed by {$name}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = 'Rozhovor ' . $interview->title;
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
