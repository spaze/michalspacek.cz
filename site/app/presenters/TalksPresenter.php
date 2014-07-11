<?php
/**
 * Talks presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TalksPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Talks */
	protected $talks;

	/** @var \MichalSpacekCz\Embed */
	protected $embed;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \MichalSpacekCz\Talks $talks
	 * @param \MichalSpacekCz\Embed $embed
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\MichalSpacekCz\Talks $talks,
		\MichalSpacekCz\Embed $embed
	)
	{
		$this->talks = $talks;
		$this->embed = $embed;
		parent::__construct($translator);
	}


	public function renderDefault()
	{
		$this->template->pageTitle = 'Přednášky';
		$this->template->upcomingTalks = $this->talks->getUpcoming();

		$talks = array();
		foreach ($this->talks->getAll() as $talk) {
			$talks[$talk->date->format('Y')][] = $talk;
		}
		$this->template->talks = $talks;
	}


	public function actionTalk($name, $slide = null)
	{
		$talk = $this->talks->get($name);
		if (!$talk) {
			throw new \Nette\Application\BadRequestException("I haven't talked about {$name}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = 'Přednáška ' . strip_tags($talk->title) . " ({$talk->event})";
		$this->template->pageHeader = $talk->title;
		$this->template->description = $talk->description;
		$this->template->href = $talk->href;
		$this->template->date = $talk->date;
		$this->template->eventHref = $talk->eventHref;
		$this->template->event = $talk->event;
		$this->template->ogImage = $talk->ogImage;
		$this->template->transcript = $talk->transcript;

		if ($slide !== null) {
			$this->template->canonicalLink = $this->link("//$name");
		}

		$this->template->slidesHref = $talk->slidesHref;
		foreach ($this->embed->getSlidesTemplateVars($talk->slidesHref, $talk->slidesEmbed, $slide) as $key => $value) {
			$this->template->$key = $value;
		}

		$this->template->videoHref = $talk->videoHref;
		foreach ($this->embed->getVideoTemplateVars($talk->videoHref, $talk->videoEmbed) as $key => $value) {
			$this->template->$key = $value;
		}
	}

}
