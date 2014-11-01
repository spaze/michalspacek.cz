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

}
