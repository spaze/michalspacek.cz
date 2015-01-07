<?php
namespace MichalSpacekCz\Templating;

class Helpers extends \Nette\Object
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/** @var string */
	protected $staticRoot;


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
		return sprintf('%s/%s',
			rtrim($this->staticRoot, '/'),
			ltrim($filename, '/')
		);
	}


	public function setStaticRoot($staticRoot)
	{
		$this->staticRoot = $staticRoot;
	}


	public function format($message)
	{
		$args = func_get_args();
		array_shift($args);
		return $this->texyFormatter->substitute($message, $args);
	}

}
