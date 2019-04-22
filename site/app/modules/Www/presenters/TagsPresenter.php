<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Articles;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Strings;
use Nette\Application\BadRequestException;

class TagsPresenter extends BasePresenter
{

	/** @var Articles */
	protected $articles;

	/** @var Texy */
	protected $texyFormatter;

	/** @var Strings */
	protected $strings;


	public function __construct(Articles $articles, Strings $strings, Texy $texyFormatter)
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
	 * @throws BadRequestException
	 */
	public function actionTag(string $tags): void
	{
		$label = $this->articles->getLabelByTags($tags);
		if (!$label) {
			throw new BadRequestException('Unknown tag');
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.label.articlesbytag', [$label]);
		$this->template->articles = $this->articles->getAllByTags($tags);
	}

}
