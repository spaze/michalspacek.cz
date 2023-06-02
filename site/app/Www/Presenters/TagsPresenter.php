<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Www\Presenters;

use MichalSpacekCz\Articles\Articles;
use MichalSpacekCz\Blog\BlogPostLocaleUrls;
use MichalSpacekCz\Formatter\TexyFormatter;
use MichalSpacekCz\Utils\Strings;
use Nette\Application\BadRequestException;
use Nette\Database\Row;

class TagsPresenter extends BasePresenter
{

	/** @var string[][] */
	private array $localeLinkParams = [];


	public function __construct(
		private readonly Articles $articles,
		private readonly Strings $strings,
		private readonly TexyFormatter $texyFormatter,
		private readonly BlogPostLocaleUrls $blogPostLocaleUrls,
	) {
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


	public function actionTag(string $tag): void
	{
		$label = $this->articles->getLabelByTag($tag);
		if (!$label) {
			throw new BadRequestException('Unknown tag');
		}

		$articles = $this->articles->getAllByTags([$tag]);
		$this->findLocaleLinkParams($articles, $tag);

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
	 * @param string $tag
	 */
	private function findLocaleLinkParams(array $articles, string $tag): void
	{
		foreach ($articles as $article) {
			$posts = $this->blogPostLocaleUrls->get($article->slug);
			if (count($posts) === 1) {
				continue; // post and tags not translated yet
			}
			$tagKey = array_search($tag, $article->slugTags);
			foreach ($posts as $post) {
				if (isset($post->slugTags[$tagKey])) {
					$this->localeLinkParams[$post->locale] = ['tag' => $post->slugTags[$tagKey]];
				}
			}
			if (isset($post) && isset($this->localeLinkParams[$post->locale])) {
				return;
			}
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
