<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\SlidesPlatform;
use MichalSpacekCz\Media\VideoThumbnails;
use MichalSpacekCz\Talks\Talks;
use MichalSpacekCz\Training\Dates;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use RuntimeException;

class TalksPresenter extends BasePresenter
{

	public function __construct(
		private readonly Talks $talks,
		private readonly Dates $trainingDates,
		private readonly VideoThumbnails $videoThumbnails,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.talks');
		$this->template->favoriteTalks = $this->talks->getFavorites();
		$this->template->upcomingTalks = $this->talks->getUpcoming();

		$talks = [];
		foreach ($this->talks->getAll() as $talk) {
			$talks[$talk->date->format('Y')][] = $talk;
		}
		$this->template->talks = $talks;
	}


	/**
	 * @param string $name
	 * @param string|null $slide
	 * @throws InvalidLinkException
	 * @throws ContentTypeException
	 */
	public function actionTalk(string $name, ?string $slide = null): void
	{
		try {
			$talk = $this->talks->get($name);
			if ($talk->slidesTalkId) {
				$slidesTalk = $this->talks->getById($talk->slidesTalkId);
				$slides = ($slidesTalk->publishSlides ? $this->talks->getSlides($slidesTalk->talkId, $slidesTalk->filenamesTalkId) : []);
				$slideNo = $this->talks->getSlideNo($talk->slidesTalkId, $slide);
				$this->template->canonicalLink = $this->link('//:Www:Talks:talk', [$slidesTalk->action]);
			} else {
				$slides = ($talk->publishSlides ? $this->talks->getSlides($talk->talkId, $talk->filenamesTalkId) : []);
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
		$this->template->videoThumbnail = $this->videoThumbnails->getVideoThumbnail($talk)->setLazyLoad(count($slides) > 3);
		$this->template->slidesPlatform = $talk->slidesHref ? SlidesPlatform::tryFromUrl($talk->slidesHref)?->getName() : null;
	}

}
