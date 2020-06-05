<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Post;

use DateTimeInterface;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use stdClass;

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

	/** @var Html<Html|string> */
	public $title;

	/** @var string */
	public $titleTexy;

	/** @var Html<Html|string> */
	public $lead;

	/** @var string */
	public $leadTexy;

	/** @var Html<Html|string> */
	public $text;

	/** @var string */
	public $textTexy;

	/** @var DateTimeInterface */
	public $published;

	/** @var string */
	public $previewKey;

	/** @var Html<Html|string> */
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

	/** @var stdClass[] */
	public $recommended;

	/** @var string */
	public $twitterCard;

	/** @var string */
	public $href;

	/** @var string|null */
	public $editSummary;

	/** @var Edit[] */
	public $edits;

	/** @var array<integer, string> */
	public array $cspSnippets = [];

	/** @var array<integer, string> */
	public array $allowedTags = [];


	public function needsPreviewKey(): bool
	{
		return $this->published > new DateTime();
	}

}
