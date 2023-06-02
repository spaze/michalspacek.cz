<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog;

use DateTime;
use DateTimeInterface;
use Nette\Utils\Html;
use stdClass;

class BlogPost
{

	public ?int $postId;

	public string $slug;

	public int $localeId;

	public ?int $translationGroupId;

	public ?string $locale;

	/** @var Html<Html|string> */
	public Html $title;

	public string $titleTexy;

	/** @var Html<Html|string>|null */
	public ?Html $lead;

	public ?string $leadTexy;

	/** @var Html<Html|string> */
	public Html $text;

	public string $textTexy;

	public ?DateTimeInterface $published;

	public ?string $previewKey;

	/** @var Html<Html|string>|null */
	public ?Html $originally;

	public ?string $originallyTexy;

	public ?string $ogImage;

	/** @var array<int, string> */
	public array $tags = [];

	/** @var array<int, string> */
	public array $slugTags = [];

	/** @var array<int, string> */
	public array $previousSlugTags = [];

	/** @var array<int, stdClass> */
	public array $recommended;

	public ?string $twitterCard;

	public string $href;

	public ?string $editSummary;

	/** @var array<int, BlogPostEdit> */
	public array $edits;

	/** @var array<int, string> */
	public array $cspSnippets = [];

	/** @var array<int, string> */
	public array $allowedTags = [];

	public bool $omitExports;


	public function needsPreviewKey(DateTime $when = new DateTime()): bool
	{
		return $this->published === null || $this->published > $when;
	}

}
