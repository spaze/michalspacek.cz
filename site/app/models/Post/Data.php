<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

/**
 * Blog post data.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Data
{
	/** @var integer */
	public $postId;

	/** @var string */
	public $slug;

	/** @var integer */
	public $localeId;

	/** @var integer */
	public $translationGroupId;

	/** @var string */
	public $locale;

	/** @var \Nette\Utils\Html */
	public $title;

	/** @var string */
	public $titleTexy;

	/** @var \Nette\Utils\Html */
	public $lead;

	/** @var string */
	public $leadTexy;

	/** @var \Nette\Utils\Html */
	public $text;

	/** @var string */
	public $textTexy;

	/** @var \DateTimeInterface */
	public $published;

	/** @var string */
	public $previewKey;

	/** @var \Nette\Utils\Html */
	public $originally;

	/** @var string */
	public $originallyTexy;

	/** @var string */
	public $ogImage;

	/** @var string[] */
	public $tags = [];

	/** @var string[] */
	public $slugTags = [];

	/** @var string[] */
	public $previousSlugTags = [];

	/** @var \stdClass[] */
	public $recommended;

	/** @var string */
	public $twitterCard;

	/** @var string */
	public $href;

	/** @var string|null */
	public $editSummary;

	/** @var Edit[] */
	public $edits;


	/**
	 * Returns true when the post needs preview key to display.
	 *
	 * @return bool
	 */
	public function needsPreviewKey(): bool
	{
		return $this->published > new \Nette\Utils\DateTime();
	}

}