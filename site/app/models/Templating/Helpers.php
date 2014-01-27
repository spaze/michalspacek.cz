<?php
namespace MichalSpacekCz\Templating;

class Helpers extends \Nette\Object
{

	/**
	 * @var string
	 */
	protected $staticRoot;
	
	/**
	 * @var \Nette\Http\IRequest
	 */
	protected $httpRequest;


	public function __construct(\Nette\Http\IRequest $httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}


	public function loader($helper)
	{
		if (method_exists($this, $helper)) {
			return [$this, $helper];
		}
	}


	public function staticUrl($filename)
	{
		return sprintf('%s://%s/%s',
			$this->httpRequest->getUrl()->getScheme(),
			rtrim($this->staticRoot, '/'),
			ltrim($filename, '/')
		);
	}


	public function setStaticRoot($staticRoot)
	{
		$this->staticRoot = $staticRoot;
	}


}
