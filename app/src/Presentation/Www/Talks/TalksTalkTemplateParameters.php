<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks;

use MichalSpacekCz\Media\Video;
use MichalSpacekCz\Talks\Slides\TalkSlideCollection;
use MichalSpacekCz\Talks\Talk;
use MichalSpacekCz\Training\Dates\UpcomingTraining;
use Nette\Utils\Html;

final readonly class TalksTalkTemplateParameters
{

	/**
	 * @param array<string, UpcomingTraining> $upcomingTrainings
	 */
	public function __construct(
		public Html $pageTitle,
		public Html $pageHeader,
		public Talk $talk,
		public ?string $slideAlias,
		public ?string $canonicalLink,
		public ?TalkSlideCollection $slides,
		public ?string $ogImage,
		public array $upcomingTrainings,
		public Video $video,
		public ?string $slidesPlatform,
	) {
	}

}
