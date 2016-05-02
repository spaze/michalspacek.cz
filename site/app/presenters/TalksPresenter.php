<?php
namespace App\Presenters;

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

	/** @var \MichalSpacekCz\Templating\Helpers */
	protected $helpers;

	/** @var \Spaze\ContentSecurityPolicy\Config */
	protected $contentSecurityPolicy;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Talks $talks
	 * @param \MichalSpacekCz\Embed $embed
	 * @param \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	 * @param \MichalSpacekCz\Templating\Helpers $helpers
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Talks $talks,
		\MichalSpacekCz\Embed $embed,
		\Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy,
		\MichalSpacekCz\Templating\Helpers $helpers
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->talks = $talks;
		$this->embed = $embed;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
		$this->helpers = $helpers;
	}


	public function renderDefault()
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


	public function actionTalk($name, $slide = null)
	{
		$talk = $this->talks->get($name);
		if (!$talk) {
			throw new \Nette\Application\BadRequestException("I haven't talked about {$name}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$slideNo = $this->talks->getSlideNo($talk->talkId, $slide);

		if ($talk->supersededByAction) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.supersededby', [$talk->supersededByTitle, "link:Talks:talk {$talk->supersededByAction}"]));
		}

		if ($talk->origAction) {
			$this->flashMessage($this->texyFormatter->translate('messages.talks.slidesorigin', [$talk->origTitle, "link:Talks:talk {$talk->origAction}"]), 'notice');
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.talk', [strip_tags($talk->title), $talk->event]);
		$this->template->pageHeader = $talk->title;
		$this->template->description = $talk->description;
		$this->template->href = $talk->href;
		$this->template->date = $talk->date;
		$this->template->eventHref = $talk->eventHref;
		$this->template->event = $talk->event;
		$this->template->ogImage = $this->getOgImage($talk->ogImage, $slideNo);
		$this->template->transcript = $talk->transcript;

		if ($slideNo !== null) {
			$this->template->canonicalLink = $this->link('//Talks:talk', $name);
		}

		$type = $this->embed->getSlidesType($talk->slidesHref);
		if ($type !== false) {
			$this->contentSecurityPolicy->addSnippet($type);
		}
		$this->template->slidesHref = $talk->slidesHref;
		$this->template->slidesEmbedType = $type;
		foreach ($this->embed->getSlidesTemplateVars($type, $talk->slidesEmbed, $slideNo) as $key => $value) {
			$this->template->$key = $value;
		}

		$type = $this->embed->getVideoType($talk->videoHref);
		if ($type !== false) {
			$this->contentSecurityPolicy->addSnippet($type);
		}
		$this->template->videoHref = $talk->videoHref;
		$this->template->videoEmbedType = $type;
		$this->template->videoEmbed = $talk->videoEmbed;
	}


	private function getOgImage($image, $slide)
	{
		if ($slide === null) {
			$slide = 1;
		}

		return sprintf($image, $slide);
	}

}
