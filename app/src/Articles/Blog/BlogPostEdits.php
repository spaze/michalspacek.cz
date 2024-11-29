<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Blog;

use DateTime;
use MichalSpacekCz\Articles\ArticleEdit;
use MichalSpacekCz\Database\TypedDatabase;
use MichalSpacekCz\DateTime\DateTimeZoneFactory;
use MichalSpacekCz\DateTime\Exceptions\InvalidTimezoneException;
use MichalSpacekCz\Formatter\TexyFormatter;

readonly class BlogPostEdits
{

	public function __construct(
		private TypedDatabase $typedDatabase,
		private DateTimeZoneFactory $dateTimeZoneFactory,
		private TexyFormatter $texyFormatter,
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
		foreach ($this->typedDatabase->fetchAll($sql, $postId) as $row) {
			assert($row->editedAt instanceof DateTime);
			assert(is_string($row->editedAtTimezone));
			assert(is_string($row->summaryTexy));
			$row->editedAt->setTimezone($this->dateTimeZoneFactory->get($row->editedAtTimezone));
			$edits[] = new ArticleEdit($row->editedAt, $this->texyFormatter->format($row->summaryTexy), $row->summaryTexy);
		}
		return $edits;
	}

}
