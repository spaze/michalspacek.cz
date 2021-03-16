<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Templating;

use MichalSpacekCz\Formatter\Texy;
use Nette\SmartObject;
use Nette\Utils\Html;

class Helpers
{

	use SmartObject;


	private Texy $texyFormatter;


	public function __construct(Texy $texyFormatter)
	{
		$this->texyFormatter = $texyFormatter;
	}


	/**
	 * @param string $helper
	 * @param mixed ...$args
	 * @return mixed
	 */
	public function loader(string $helper, ...$args)
	{
		if (method_exists($this, $helper)) {
			return $this->$helper(...$args);
		} else {
			return null;
		}
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
