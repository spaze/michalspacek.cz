<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

/**
 * Tags presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TagsPresenter extends BasePresenter
{

	/** @var \MichalSpacekCz\Articles */
	protected $articles;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var \MichalSpacekCz\Strings */
	protected $strings;


	/**
	 * @param \MichalSpacekCz\Articles $articles
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(\MichalSpacekCz\Articles $articles, \MichalSpacekCz\Strings $strings, \MichalSpacekCz\Formatter\Texy $texyFormatter)
	{
		$this->articles = $articles;
		$this->strings = $strings;
		$this->texyFormatter = $texyFormatter;
		parent::__construct();
	}


	public function actionDefault(): void
	{
		$this->template->pageTitle = $this->translator->translate('messages.label.tags');
		$tags = [];
		foreach ($this->articles->getAllTags() as $slug => $tag) {
			$tags[$this->strings->getInitialLetterUppercase($tag)][$slug] = $tag;
		}
		$this->template->allTags = $tags;
	}


	/**
	 * @param string $tags
	 * @throws \Nette\Application\BadRequestException
	 */
	public function actionTag(string $tags): void
	{
		$label = $this->articles->getLabelByTags($tags);
		if (!$label) {
			throw new \Nette\Application\BadRequestException('Unknown tag', \Nette\Http\IResponse::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.label.articlesbytag', [$label]);
		$this->template->articles = $this->articles->getAllByTags($tags);
	}

}
