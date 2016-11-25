<?php
namespace MichalSpacekCz;

/**
 * Files model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Files
{

	/** @var \Nette\Database\Context */
	protected $database;

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;


	public function __construct(\Nette\Database\Context $context, \Nette\Http\IRequest $httpRequest)
	{
		$this->database = $context;
		$this->httpRequest = $httpRequest;
	}


	public function logDownload($id)
	{
		$datetime = new \DateTime();
		$this->database->query('INSERT INTO file_downloads', array(
			'key_file'      => $id,
			'ip'            => $this->httpRequest->getRemoteAddress(),
			'user_agent'    => (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null),
			'time'          => $datetime,
			'time_timezone' => $datetime->getTimezone()->getName(),
		));
		return $this->database->getInsertId();
	}

}
