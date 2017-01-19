<?php
namespace MichalSpacekCz\Templating;

class Helpers extends \Nette\Object
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;


	public function __construct(\MichalSpacekCz\Formatter\Texy $texyFormatter)
	{
		$this->texyFormatter = $texyFormatter;
	}


	public function loader($helper)
	{
		if (method_exists($this, $helper)) {
			return call_user_func_array([$this, $helper], array_slice(func_get_args(), 1));
		} else {
			return null;
		}
	}


	public function staticUrl($filename)
	{
		return sprintf('%s/%s', $this->texyFormatter->getStaticRoot(), ltrim($filename, '/'));
	}


	public function staticImageUrl($filename)
	{
		return $this->texyFormatter->getImagesRoot($filename);
	}


	public function format($message)
	{
		$args = func_get_args();
		array_shift($args);
		return $this->texyFormatter->substitute($message, $args);
	}

}
