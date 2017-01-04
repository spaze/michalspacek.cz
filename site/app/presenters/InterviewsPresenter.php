<?php
namespace App\Presenters;

/**
 * Interviews presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class InterviewsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Interviews */
	protected $interviews;

	/** @var \MichalSpacekCz\Embed */
	protected $embed;

	/** @var \Spaze\ContentSecurityPolicy\Config */
	protected $contentSecurityPolicy;


	/**
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 * @param \MichalSpacekCz\Interviews $interviews
	 * @param \MichalSpacekCz\Embed $embed
	 * @param \Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	 */
	public function __construct(
		\MichalSpacekCz\Formatter\Texy $texyFormatter,
		\MichalSpacekCz\Interviews $interviews,
		\MichalSpacekCz\Embed $embed,
		\Spaze\ContentSecurityPolicy\Config $contentSecurityPolicy
	)
	{
		$this->texyFormatter = $texyFormatter;
		$this->interviews = $interviews;
		$this->embed = $embed;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
		parent::__construct();
	}


	public function renderDefault()
	{
		$this->template->pageTitle = $this->translator->translate('messages.title.interviews');
		$this->template->interviews = $this->interviews->getAll();
	}


	public function actionInterview($name)
	{
		$interview = $this->interviews->get($name);
		if (!$interview) {
			throw new \Nette\Application\BadRequestException("I haven't been interviewed by {$name}, yet", \Nette\Http\Response::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.title.interview', [$interview->title]);
		$this->template->pageHeader = $interview->title;
		$this->template->description = $interview->description;
		$this->template->href = $interview->href;
		$this->template->date = $interview->date;
		$this->template->audioHref = $interview->audioHref;
		$this->template->videoHref = $interview->videoHref;
		$this->template->sourceName = $interview->sourceName;
		$this->template->sourceHref = $interview->sourceHref;

		$type = $this->embed->getVideoType($interview->videoHref);
		if ($type !== false) {
			$this->contentSecurityPolicy->addSnippet($type);
		}
		$this->template->videoEmbedType = $type;
		$this->template->videoEmbed = $interview->videoEmbed;
	}

}
