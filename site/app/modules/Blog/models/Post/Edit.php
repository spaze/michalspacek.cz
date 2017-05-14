<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog\Post;

/**
 * Blog post edit.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Edit
{
	/** @var \DateTime */
	public $editedAt;

	/** @var \Nette\Utils\Html */
	public $summary;

	/** @var string */
	public $summaryTexy;

}
