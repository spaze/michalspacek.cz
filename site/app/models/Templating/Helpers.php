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


	public function staticUrl($filename, $host = null)
	{
		return sprintf('%s://%s.%s/%s',
			$this->httpRequest->getUrl()->getScheme(),
			($host ?: $this->context->params['domain']['defaultHost']),
			$this->context->params['domain']['staticRoot'],
			$filename
		);
	}


}