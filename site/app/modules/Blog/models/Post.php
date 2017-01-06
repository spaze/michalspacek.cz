<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Blog;

/**
 * Blog post service.
 *
 * @author Michal Špaček
 * @package michalspacek.cz
 */
class Post
{

	/** @var PostLoader */
	protected $loader;

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;


	/**
	 * @param PostLoader $loader
	 * @param \MichalSpacekCz\Formatter\Texy $texyFormatter
	 */
	public function __construct(PostLoader $loader, \MichalSpacekCz\Formatter\Texy $texyFormatter)
	{
		$this->loader = $loader;
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * Get post.
	 *
	 * @param string $post
	 * @return \Nette\Database\Row|null
	 */
	public function get(string $post)
	{
		$result = $this->loader->fetch($post);
		$result->title = $this->texyFormatter->format($result->title);
		$result->text = $this->texyFormatter->formatBlock($result->text);
		return $result;
	}

}
