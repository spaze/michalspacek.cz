<?php
namespace MichalSpacekCz;

/**
 * Reports service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Reports
{

	/** @var boolean */
	public $enabledLocally;

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;


	/**
	 * @param \Nette\Database\Context $context
	 * @param \Nette\Http\IRequest $httpRequest
	 */
	public function __construct(\Nette\Database\Context $context, \Nette\Http\IRequest $httpRequest)
	{
		$this->database = $context;
		$this->httpRequest = $httpRequest;
	}


	/**
	 * @param string $userAgent
	 * @param \stdClass $report JSON data
	 */
	public function storeCspReport($userAgent, \stdClass $report)
	{
		$columns = array(
			'blocked-uri' => 'blocked_uri',
			'document-uri' => 'document_uri',
			'effective-directive' => 'effective_directive',
			'original-policy' => 'original_policy',
			'referrer' => 'referrer',
			'status-code' => 'status_code',
			'violated-directive' => 'violated_directive',
			'source-file' => 'source_file',
			'line-number' => 'line_number',
			'column-number' => 'column_number',
		);

		$datetime = new \DateTime();
		$data = array(
			'ip' => $this->httpRequest->getRemoteAddress(),
			'datetime' => $datetime,
			'datetime_timezone' => $datetime->getTimezone()->getName(),
			'user_agent' => $userAgent,
		);
		foreach ($columns as $key => $value) {
			$data[$value] = (isset($report->$key) ? $report->$key : null);
		}

		$this->database->query('INSERT INTO reports_csp', $data);
	}

	/**
	 * @param string $userAgent
	 * @param \stdClass $report JSON data
	 */
	public function storeXssReport($userAgent, \stdClass $report)
	{
		$columns = array(
			'request-url' => 'request_url',
			'request-body' => 'request_body',
		);

		$datetime = new \DateTime();
		$data = array(
			'ip' => $this->httpRequest->getRemoteAddress(),
			'datetime' => $datetime,
			'datetime_timezone' => $datetime->getTimezone()->getName(),
			'user_agent' => $userAgent,
		);
		foreach ($columns as $key => $value) {
			$data[$value] = (isset($report->$key) ? $report->$key : null);
		}

		$this->database->query('INSERT INTO reports_xss', $data);
	}

}
