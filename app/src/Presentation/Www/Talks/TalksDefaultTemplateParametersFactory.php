<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Presentation\Www\Talks;

use Contributte\Translation\Translator;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use MichalSpacekCz\Talks\Talks;

final readonly class TalksDefaultTemplateParametersFactory
{

	public function __construct(
		private Talks $talks,
		private Translator $translator,
	) {
	}


	/**
	 * @throws ContentTypeException
	 */
	public function create(): TalksDefaultTemplateParameters
	{
		$talks = [];
		foreach ($this->talks->getAll() as $talk) {
			$talks[(int)$talk->getDate()->format('Y')][] = $talk;
		}
		return new TalksDefaultTemplateParameters(
			$this->translator->translate('messages.title.talks'),
			$this->talks->getFavorites(),
			$this->talks->getUpcoming(),
			$talks,
		);
	}

}
