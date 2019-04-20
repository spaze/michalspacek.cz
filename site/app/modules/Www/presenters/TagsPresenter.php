<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Articles;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Strings;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

/**
 * Tags presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class TagsPresenter extends BasePresenter
{

	/** @var Articles */
	protected $articles;

	/** @var Texy */
	protected $texyFormatter;

	/** @var Strings */
	protected $strings;


	/**
	 * @param Articles $articles
	 * @param Texy $texyFormatter
	 */
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
			throw new BadRequestException('Unknown tag', IResponse::S404_NOT_FOUND);
		}

		$this->template->pageTitle = $this->texyFormatter->translate('messages.label.articlesbytag', [$label]);
		$this->template->articles = $this->articles->getAllByTags($tags);
	}

}
