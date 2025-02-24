<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use Contributte\Translation\Translator;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Interviews\Exceptions\InterviewDoesNotExistException;
use MichalSpacekCz\Interviews\Interviews;
use MichalSpacekCz\Media\Exceptions\ContentTypeException;
use Nette\Application\BadRequestException;

final class InterviewsPresenter extends BasePresenter
{

	public function __construct(
		private readonly TexyFormatter $texyFormatter,
		private readonly Interviews $interviews,
		private readonly Translator $translator,
	) {
		parent::__construct();
	}


	public function renderDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.interviews');
		$this->template->interviews = $this->interviews->getAll();
	}


	/**
	 * @throws ContentTypeException
	 */
	public function actionInterview(string $name): void
	{
		try {
			$interview = $this->interviews->get($name);
		} catch (InterviewDoesNotExistException $e) {
			throw new BadRequestException($e->getMessage(), previous: $e);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.interview', [$interview->getTitle()]);
		$this->template->pageHeader = $interview->getTitle();
		$this->template->description = $interview->getDescription();
		$this->template->href = $interview->getHref();
		$this->template->date = $interview->getDate();
		$this->template->audioHref = $interview->getAudioHref();
		$this->template->sourceName = $interview->getSourceName();
		$this->template->sourceHref = $interview->getSourceHref();
		$this->template->video = $interview->getVideo();
	}

}
