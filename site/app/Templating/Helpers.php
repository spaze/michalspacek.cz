<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Formatter\Texy;
use Nette\Utils\Html;

class Helpers
{

	private Texy $texyFormatter;


	public function __construct(Texy $texyFormatter)
	{
		$this->texyFormatter = $texyFormatter;
	}


	public function loader(string $filter): ?callable
	{
		$callback = [$this, $filter];
		return is_callable($callback) ? $callback : null;
	}


	public function staticUrl(string $filename): string
	{
		return sprintf('%s/%s', $this->texyFormatter->getStaticRoot(), ltrim($filename, '/'));
	}


	public function staticImageUrl(string $filename): string
	{
		return $this->texyFormatter->getImagesRoot($filename);
	}


	/**
	 * @param string $message
	 * @return Html<Html|string>
	 */
	public function format(string $message): Html
	{
		$args = func_get_args();
		array_shift($args);
		return $this->texyFormatter->substitute($message, $args);
	}

}
