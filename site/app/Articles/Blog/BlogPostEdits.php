<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Formatter\TexyFormatter;
use Nette\Database\Explorer;

class BlogPostEdits
{

	public function __construct(
		private readonly Explorer $database,
		private readonly DateTimeZoneFactory $dateTimeZoneFactory,
		private readonly TexyFormatter $texyFormatter,
	) {
	}


	/**
	 * @return list<ArticleEdit>
	 * @throws InvalidTimezoneException
	 */
	public function getEdits(int $postId): array
	{
		$sql = 'SELECT
				edited_at AS editedAt,
				edited_at_timezone AS editedAtTimezone,
				summary AS summaryTexy
			FROM blog_post_edits
			WHERE key_blog_post = ?
			ORDER BY edited_at DESC';
		$edits = [];
		foreach ($this->database->fetchAll($sql, $postId) as $row) {
			$editedAt = $row->editedAt;
			$editedAt->setTimezone($this->dateTimeZoneFactory->get($row->editedAtTimezone));
			$edits[] = new ArticleEdit($editedAt, $this->texyFormatter->format($row->summaryTexy), $row->summaryTexy);
		}
		return $edits;
	}

}
