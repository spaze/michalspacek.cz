<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use Nette\Utils\Html;

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

	/** @var \MichalSpacekCz\Embed */
	protected $embed;

	/** @var \Nette\Application\LinkGenerator */
	protected $linkGenerator;

	/** @var \MichalSpacekCz\Templating\Helpers */
	protected $helpers;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Talks $talks
	 * @param \MichalSpacekCz\Embed $embed
	 * @param \Nette\Application\LinkGenerator $linkGenerator
	 * @param \MichalSpacekCz\Templating\Helpers $helpers
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Talks $talks,
		\MichalSpacekCz\Embed $embed,
		\Nette\Application\LinkGenerator $linkGenerator,
		\MichalSpacekCz\Templating\Helpers $helpers
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->talks = $talks;
		$this->embed = $embed;
		$this->linkGenerator = $linkGenerator;
		$this->helpers = $helpers;
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.talks');
		$this->template->favoriteTalks = $this->talks->getFavorites();
		$this->template->upcomingTalks = $this->talks->getUpcoming();

		$talks = array();
		foreach ($this->talks->getAll() as $talk) {
			$talks[$talk->date->format('Y')][] = $talk;
		}
		$this->template->talks = $talks;
	}


	public function actionTalk(string $name, ?string $slide = null): void
	{
		try {
			$talk = $this->talks->get($name);
			$slideNo = $this->talks->getSlideNo($talk->talkId, $slide);
			$slides = ($talk->publishSlides ? $this->talks->getSlides($talk->talkId) : []);
		} catch (\RuntimeException $e) {
			throw new \Nette\Application\BadRequestException($e->getMessage(), \Nette\Http\Response::S404_NOT_FOUND);
		}

		if ($talk->supersededByAction) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.supersededby', [$talk->supersededByTitle, "link:Www:Talks:talk {$talk->supersededByAction}"]));
		}

		if ($talk->origAction) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.slidesorigin', [$talk->origTitle, "link:Www:Talks:talk {$talk->origAction}"]), 'notice');
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $talk);
		$this->template->pageHeader = $talk->title;
		$this->template->talk = $talk;
		$this->template->slideNo = $slideNo;
		$this->template->slides = $slides;
		$this->template->canonicalLink = ($slideNo !== null ? $this->linkGenerator->link('Www:Talks:talk', [$talk->action]) : null);
		$this->template->ogImage = ($slides[$slideNo ?? 1]->image ?? ($talk->ogImage !== null ? sprintf($talk->ogImage, $slideNo ?? 1) : null));
		$this->template->useAlternativeImages = $this->talks->useAlternativeImages();

		$this->template->slidesHref = $talk->slidesHref;
		foreach ($this->embed->getSlidesTemplateVars($talk, $slideNo) as $key => $value) {
			$this->template->$key = $value;
		}

		$this->template->videoHref = $talk->videoHref;
		foreach ($this->embed->getVideoTemplateVars($talk) as $key => $value) {
			$this->template->$key = $value;
		}
	}

}
