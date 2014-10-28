<?php
namespace MichalSpacekCz;

/**
 * ContentSecurityPolicy service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ContentSecurityPolicy
{

	/** @var string */
	protected $rootDomain;

	/** @var array of host => array of policies */
	protected $policy = array();

	/** @var \Nette\Database\Connection */
	protected $database;

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;


	public function __construct(\Nette\Database\Connection $connection, \Nette\Http\IRequest $httpRequest)
	{
		$this->database = $connection;
		$this->httpRequest = $httpRequest;
	}


	public function setRootDomain($rootDomain)
	{
		$this->rootDomain = $rootDomain;
	}


	public function setPolicy($policy)
	{
		foreach ($policy as $host => $sources) {
			$this->policy["{$host}.{$this->rootDomain}"] = $sources;
		}
	}


	public function getHeader()
	{
		$host = $this->httpRequest->getUrl()->getHost();

		if (!isset($this->policy[$host])) {
			return false;
		}

		$policy = array();
		foreach ($this->policy[$host] as $directive => $sources) {
			$policy[] = "{$directive} {$sources}";
		}
		return (isset($this->policy[$host]) ? implode('; ', $policy) : false);
	}


	/**
	 * @param string $userAgent
	 * @param \stdClass $report JSON data
	 */
	public function storeReport($userAgent, \stdClass $report)
	{
		$datetime = new \DateTime();
		$this->database->query('INSERT INTO csp_reports', array(
			'datetime' => $datetime,
			'datetime_timezone' => $datetime->getTimezone()->getName(),
			'user_agent' => $userAgent,
			'blocked_uri' => $report->{'blocked-uri'},
			'document_uri' => $report->{'document-uri'},
			'effective_directive' => (isset($report->{'effective-directive'}) ? $report->{'effective-directive'} : null),
			'original_policy' => $report->{'original-policy'},
			'referrer' => $report->{'referrer'},
			'status_code' => (isset($report->{'status-code'}) ? $report->{'status-code'} : null),
			'violated_directive' => $report->{'violated-directive'},
			'source_file' => (isset($report->{'source-file'}) ? $report->{'source-file'} : null),
			'line_number' => (isset($report->{'line-number'}) ? $report->{'line-number'} : null),
			'column_number' => (isset($report->{'column-number'}) ? $report->{'column-number'} : null),
		));
	}

}
