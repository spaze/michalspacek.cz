<?php
/**
 * Report presenter.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ReportPresenter extends BasePresenter
{

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;

	/** @var \Nette\Http\IResponse */
	protected $httpResponse;

	/** @var \MichalSpacekCz\Reports */
	protected $reports;


	/**
	 * @param \Nette\Localization\ITranslator $translator
	 * @param \Nette\Http\IRequest $httpRequest
	 * @param \Nette\Http\IResponse $httpResponse
	 * @param \MichalSpacekCz\Reports $reports
	 */
	public function __construct(
		\Nette\Localization\ITranslator $translator,
		\Nette\Http\IRequest $httpRequest,
		\Nette\Http\IResponse $httpResponse,
		\MichalSpacekCz\Reports $reports
	)
	{
		parent::__construct($translator);
		$this->httpRequest = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->reports = $reports;
	}


	public function actionCsp()
	{
		$report = \Nette\Utils\Json::decode(file_get_contents('php://input'));
		$userAgent = $this->httpRequest->getHeader('User-Agent');
		if (isset($report->{'csp-report'})) {
			$this->reports->storeCspReport($userAgent, $report->{'csp-report'});
		}
		$this->terminate();
	}

	public function actionXss()
	{
		$report = \Nette\Utils\Json::decode(file_get_contents('php://input'));
		$userAgent = $this->httpRequest->getHeader('User-Agent');
		if (isset($report->{'xss-report'})) {
			$this->reports->storeXssReport($userAgent, $report->{'xss-report'});
		}
		$this->terminate();
	}

}
