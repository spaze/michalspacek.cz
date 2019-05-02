<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Embed;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Talks;
use MichalSpacekCz\Templating\Helpers;
use MichalSpacekCz\Training\Dates;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use RuntimeException;

class TalksPresenter extends BasePresenter
{

	/** @var Texy */
	protected $texyFormatter;

	/** @var Talks */
	protected $talks;

	/** @var Embed */
	protected $embed;

	/** @var Helpers */
	protected $helpers;

	/** @var Dates */
	private $trainingDates;


	public function __construct(Texy $texyFormatter, Talks $talks, Embed $embed, Helpers $helpers, Dates $trainingDates)
	{
		$this->texyFormatter = $texyFormatter;
		$this->talks = $talks;
		$this->embed = $embed;
		$this->helpers = $helpers;
		$this->trainingDates = $trainingDates;
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


	/**
	 * @param string $name
	 * @param string|null $slide
	 * @throws BadRequestException
	 * @throws InvalidLinkException
	 */
	public function actionTalk(string $name, ?string $slide = null): void
	{
		try {
			$talk = $this->talks->get($name);
			if ($talk->slidesTalkId) {
				$slidesTalk = $this->talks->getById($talk->slidesTalkId);
				$slides = ($slidesTalk->publishSlides ? $this->talks->getSlides($talk->slidesTalkId) : []);
				$slideNo = $this->talks->getSlideNo($talk->slidesTalkId, $slide);
				$this->template->canonicalLink = $this->link('//:Www:Talks:talk', [$slidesTalk->action]);
			} else {
				$slides = ($talk->publishSlides ? $this->talks->getSlides($talk->talkId) : []);
				$slideNo = $this->talks->getSlideNo($talk->talkId, $slide);
				if ($slideNo !== null) {
					$this->template->canonicalLink = $this->link('//:Www:Talks:talk', [$talk->action]);
				}
			}
		} catch (RuntimeException $e) {
			throw new BadRequestException($e->getMessage());
		}

		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $talk);
		$this->template->pageHeader = $talk->title;
		$this->template->talk = $talk;
		$this->template->slideNo = $slideNo;
		$this->template->slides = $slides;
		$this->template->ogImage = ($slides[$slideNo ?? 1]->image ?? ($talk->ogImage !== null ? sprintf($talk->ogImage, $slideNo ?? 1) : null));
		$this->template->upcomingTrainings = $this->trainingDates->getPublicUpcoming();
		foreach ($this->embed->getSlidesTemplateVars($talk, $slideNo) as $key => $value) {
			$this->template->$key = $value;
		}
		foreach ($this->embed->getVideoTemplateVars($talk) as $key => $value) {
			$this->template->$key = $value;
		}
	}

}
