<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Application\Locale\LocaleLinkGenerator;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\SlidesPlatform;
use MichalSpacekCz\Talks\Exceptions\TalkDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlideDoesNotExistException;
use MichalSpacekCz\Talks\Slides\TalkSlides;
use MichalSpacekCz\Talks\TalkLocaleUrls;
use MichalSpacekCz\Talks\Talks;
use MichalSpacekCz\Talks\TalksList;
use MichalSpacekCz\Talks\TalksListFactory;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Nette\Application\BadRequestException;
use Nette\Application\UI\InvalidLinkException;
use Nette\Http\IResponse;
use Override;

final class TalksPresenter extends BasePresenter
{

	/** @var array<string, array{name: string}> */
	private array $localeLinkParams = [];


	public function __construct(
		private readonly Talks $talks,
		private readonly TalkSlides $talkSlides,
		private readonly TalkLocaleUrls $talkLocaleUrls,
		private readonly UpcomingTrainingDates $upcomingTrainingDates,
		private readonly TalksListFactory $talksListFactory,
		private readonly LocaleLinkGenerator $localeLinkGenerator,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	/**
	 * @throws ContentTypeException
	 */
	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.talks');
		$this->template->favoriteTalks = $this->talks->getFavorites();
		$this->template->upcomingTalks = $this->talks->getUpcoming();

		$talks = [];
		foreach ($this->talks->getAll() as $talk) {
			$talks[$talk->getDate()->format('Y')][] = $talk;
		}
		$this->template->talks = $talks;
	}


	/**
	 * @param string $name
	 * @param string|null $slide
	 * @throws InvalidLinkException
	 * @throws ContentTypeException
	 * @throws TalkSlideDoesNotExistException
	 */
	public function actionTalk(string $name, ?string $slide = null): void
	{
		try {
			$talk = $this->talks->get($name);
			if ($talk->getLocale() !== $this->translator->getDefaultLocale()) {
				$links = $this->localeLinkGenerator->links(parent::getLocaleLinkAction(), parent::getLocaleLinkParams());
				$this->redirectUrl($links[$talk->getLocale()]->getUrl(), IResponse::S301_MovedPermanently);
			}
			$slidesTalkId = $talk->getSlidesTalkId();
			if ($slidesTalkId !== null) {
				$slidesTalk = $this->talks->getById($slidesTalkId);
				$slides = $slidesTalk->isPublishSlides() ? $this->talkSlides->getSlides($slidesTalk) : null;
				$slideNo = $this->talkSlides->getSlideNo($slidesTalkId, $slide);
				$this->template->canonicalLink = $this->link('//:Www:Talks:talk', [$slidesTalk->getAction()]);
			} else {
				$slides = $talk->isPublishSlides() ? $this->talkSlides->getSlides($talk) : null;
				$slideNo = $this->talkSlides->getSlideNo($talk->getId(), $slide);
				if ($slideNo !== null) {
					$this->template->canonicalLink = $this->link('//:Www:Talks:talk', [$talk->getAction()]);
				}
			}
			$talkOgImage = $talk->getOgImage();
			$ogImage = $slides?->getByNumber($slideNo ?? 1)->getImage() ?? ($talkOgImage !== null ? sprintf($talkOgImage, $slideNo ?? 1) : null);
		} catch (TalkSlideDoesNotExistException | TalkDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}
		foreach ($this->talkLocaleUrls->get($talk) as $locale => $action) {
			$this->localeLinkParams[$locale] = ['name' => $action];
		}

		$slidesHref = $talk->getSlidesHref();
		$this->template->pageTitle = $this->talks->pageTitle('messages.title.talk', $talk);
		$this->template->pageHeader = $talk->getTitle();
		$this->template->talk = $talk;
		$this->template->slideNo = $slideNo;
		$this->template->slides = $slides;
		$this->template->ogImage = $ogImage;
		$this->template->upcomingTrainings = $this->upcomingTrainingDates->getPublicUpcoming();
		$this->template->video = $talk->getVideo()->setLazyLoad($slides !== null && count($slides) > 3);
		$this->template->slidesPlatform = $slidesHref !== null ? SlidesPlatform::tryFromUrl($slidesHref)?->getName() : null;
	}


	#[Override]
	protected function getLocaleLinkAction(): string
	{
		return (count($this->localeLinkParams) > 1 ? parent::getLocaleLinkAction() : 'Www:Talks:');
	}


	/**
	 * @return array<string, array{name: string}>
	 */
	#[Override]
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams;
	}


	protected function createComponentTalksList(): TalksList
	{
		return $this->talksListFactory->create();
	}

}
