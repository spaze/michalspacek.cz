<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks;

use Contributte\Translation\Translator;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Media\SlidesPlatform;
use MichalSpacekCz\Presentation\Www\Talks\Exceptions\IncorrectSlideAliasInUrlException;
use MichalSpacekCz\Presentation\Www\Talks\Exceptions\TalkExistsInOtherLocaleException;
use MichalSpacekCz\Talks\Exceptions\TalkDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlideAliasDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlideNumberDoesNotExistException;
use MichalSpacekCz\Talks\Exceptions\TalkSlidesNotPublishedException;
use MichalSpacekCz\Talks\Slides\TalkSlides;
use MichalSpacekCz\Talks\Talks;
use MichalSpacekCz\Training\Dates\UpcomingTrainingDates;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;

final readonly class TalksTalkTemplateParametersFactory
{

	public function __construct(
		private Talks $talks,
		private TalkSlides $talkSlides,
		private UpcomingTrainingDates $upcomingTrainingDates,
		private Translator $translator,
		private LinkGenerator $linkGenerator,
	) {
	}


	/**
	 * @throws ContentTypeException
	 * @throws TalkDoesNotExistException
	 * @throws TalkExistsInOtherLocaleException
	 * @throws TalkSlideAliasDoesNotExistException
	 * @throws TalkSlidesNotPublishedException
	 * @throws IncorrectSlideAliasInUrlException
	 * @throws InvalidLinkException
	 */
	public function create(string $talkName, ?string $slide = null): TalksTalkTemplateParameters
	{
		$talk = $this->talks->get($talkName);
		if ($talk->getLocale() !== $this->translator->getDefaultLocale()) {
			throw new TalkExistsInOtherLocaleException($talk->getLocale());
		}

		if ($talk->getSlidesTalkId() !== null) {
			$slidesTalk = $this->talks->getById($talk->getSlidesTalkId());
			$slides = $slidesTalk->isPublishSlides() ? $this->talkSlides->getSlides($slidesTalk) : null;
			$action = $slidesTalk->getAction();
		} else {
			$slides = $talk->isPublishSlides() ? $this->talkSlides->getSlides($talk) : null;
			$action = $talk->getAction();
		}

		if ($slide !== null) {
			if ($this->talkSlides->isNumberSlideAlias($slide)) {
				try {
					$alias = $slides?->getByNumber((int)$slide)->getAlias();
				} catch (TalkSlideNumberDoesNotExistException) {
					$alias = null;
				}
				throw new IncorrectSlideAliasInUrlException($alias);
			} elseif ($slides === null) {
				throw new TalkSlidesNotPublishedException($talk->getId());
			} else {
				$slides->getByAlias($slide); // Check that the slide exists
			}
		}

		return new TalksTalkTemplateParameters(
			$this->talks->pageTitle('messages.title.talk', $talk),
			$talk->getTitle(),
			$talk,
			$slide,
			$this->linkGenerator->link('//:Www:Talks:talk', [$action]),
			$slides,
			$this->talkSlides->getOgImage($slide, $slides) ?? $talk->getOgImage(),
			$this->upcomingTrainingDates->getPublicUpcoming(),
			$talk->getVideo()->setLazyLoad($slides !== null && count($slides) > 3),
			$talk->getSlidesHref() !== null ? SlidesPlatform::tryFromUrl($talk->getSlidesHref())?->getName() : null,
		);
	}

}
