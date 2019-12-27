<?php
declare(strict_types = 1);

namespace App\WwwModule\Presenters;

use MichalSpacekCz\Articles;
use MichalSpacekCz\Formatter\Texy;
use MichalSpacekCz\Post\LocaleUrls;
use MichalSpacekCz\Strings;
use Nette\Application\BadRequestException;
use Nette\Database\Row;

class TagsPresenter extends BasePresenter
{

	/** @var Articles */
	protected $articles;

	/** @var Texy */
	protected $texyFormatter;

	/** @var Strings */
	protected $strings;

	/** @var LocaleUrls */
	private $localeUrls;

	/** @var string[][] */
	private $localeLinkParams = [];


	public function __construct(Articles $articles, Strings $strings, Texy $texyFormatter, LocaleUrls $localeUrls)
	{
		$this->articles = $articles;
		$this->strings = $strings;
		$this->texyFormatter = $texyFormatter;
		$this->localeUrls = $localeUrls;
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

		$articles = $this->articles->getAllByTags($tags);
		$this->findLocaleLinkParams($articles, $tags);

		$this->template->pageTitle = $this->texyFormatter->translate('messages.label.articlesbytag', [$label]);
		$this->template->articles = $articles;
	}


	/**
	 * Find translated tags.
	 *
	 * Tags in various locales must have the same order, e.g.:
	 * - tags in English: passwords, machine
	 * - tags in Czech: hesla, stroj
	 * This seems a bit weird but otherwise, we'd have to use and build and maintain a translation table for tags. Thanks, but no thanks.
	 *
	 * @param Row[] $articles
	 * @param string $tags
	 */
	private function findLocaleLinkParams(array $articles, string $tags): void
	{
		foreach ($articles as $article) {
			$posts = $this->localeUrls->get($article->slug);
			if (count($posts) === 1) {
				continue;  // post and tags not translated yet
			}
			$tagKey = array_search($tags, $article->slugTags);
			foreach ($posts as $post) {
				$this->localeLinkParams[$post->locale] = ['tags' => $post->slugTags[$tagKey]];
			}
			return;
		}
	}


	/**
	 * Get original module:presenter:action for locale links.
	 *
	 * @return string
	 */
	protected function getLocaleLinkAction(): string
	{
		return (count($this->localeLinkParams) > 1 ? parent::getLocaleLinkAction() : 'Www:Tags:');
	}


	/**
	 * Translated locale parameters for tags.
	 *
	 * @return string[][]
	 */
	protected function getLocaleLinkParams(): array
	{
		return $this->localeLinkParams;
	}

}
