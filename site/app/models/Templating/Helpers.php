<?php
namespace MichalSpacekCz\Templating;

class Helpers extends \Nette\Object
{

	/**
	 * @var \Nette\DI\Container
	 */
	protected $context;
	
	/**
	 * @var \Nette\Http\IRequest
	 */
	protected $httpRequest;


	public function __construct(\Nette\DI\Container $context, \Nette\Http\IRequest $httpRequest)
	{
		$this->context = $context;
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
			rtrim($this->context->params['domain']['sharedStaticRoot'], '/'),
			ltrim($filename, '/')
		);
	}


}