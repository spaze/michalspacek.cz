<?php
namespace MichalSpacekCz\Templating;

class Helpers extends \Nette\Object
{

	/** @var \MichalSpacekCz\Formatter\Texy */
	protected $texyFormatter;

	/**
	 * Static files root FQDN, no trailing slash.
	 *
	 * @var string
	 */
	protected $staticRoot;

	/**
	 * Images root, just directory no FQND, no leading slash, no trailing slash.
	 *
	 * @var string
	 */
	protected $imagesRoot;


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
		return sprintf('%s/%s', $this->staticRoot, ltrim($filename, '/'));
	}


	public function staticImageUrl($filename)
	{
		return sprintf('%s/%s/%s', $this->staticRoot, $this->imagesRoot, ltrim($filename, '/'));
	}


	public function setStaticRoot($staticRoot)
	{
		$this->staticRoot = rtrim($staticRoot, '/');
	}


	public function setImagesRoot($imagesRoot)
	{
		$this->imagesRoot = trim($imagesRoot, '/');
	}


	public function format($message)
	{
		$args = func_get_args();
		array_shift($args);
		return $this->texyFormatter->substitute($message, $args);
	}

}
