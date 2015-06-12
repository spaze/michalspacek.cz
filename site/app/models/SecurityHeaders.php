<?php
namespace MichalSpacekCz;

/**
 * SecurityHeaders service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class SecurityHeaders
{

	/** @var string */
	protected $rootDomain;

	/** @var array of host => headers */
	protected $extraHeaders = array();

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var ContentSecurityPolicy */
	protected $contentSecurityPolicy;


	/**
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 * @param Reports $reports
	 */
	public function __construct(
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\IResponse $httpResponse,
		ContentSecurityPolicy $contentSecurityPolicy
	)
	{
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	public function setRootDomain($rootDomain)
	{
		$this->rootDomain = $rootDomain;
	}


	public function setExtraHeaders($extraHeaders)
	{
		foreach ($extraHeaders as $host => $headers) {
			$this->extraHeaders["{$host}.{$this->rootDomain}"] = $headers;
		}
	}


	public function sendHeaders()
	{
		if ($this->httpRequest->isSecured()) {
			$this->httpResponse->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
		}

		$header = $this->contentSecurityPolicy->getHeader();
		if ($header !== false) {
			$this->httpResponse->setHeader('Content-Security-Policy', $header);
		}

		$host = $this->httpRequest->getUrl()->getHost();

		if (isset($this->extraHeaders[$host])) {
			foreach ($this->extraHeaders[$host] as $name => $value) {
				$this->httpResponse->setHeader($name, $value);
			}
		}
	}

}
