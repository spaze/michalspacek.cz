<?php
/**
 * CspReport presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class CspReportPresenter extends BasePresenter
{

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var \MichalSpacekCz\ContentSecurityPolicy */
	protected $contentSecurityPolicy;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 * @param \MichalSpacekCz\ContentSecurityPolicy $contentSecurityPolicy
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\IResponse $httpResponse,
		\MichalSpacekCz\ContentSecurityPolicy $contentSecurityPolicy
	)
	{
		parent::__construct($translator);
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->contentSecurityPolicy = $contentSecurityPolicy;
	}


	public function actionDefault()
	{
		$report = \Nette\Utils\Json::decode(file_get_contents('php://input'));
		$userAgent = $this->httpRequest->getHeader('User-Agent');
		if (isset($report->{'csp-report'})) {
			$this->contentSecurityPolicy->storeReport($userAgent, $report->{'csp-report'});
		}
		$this->terminate();
	}

}
