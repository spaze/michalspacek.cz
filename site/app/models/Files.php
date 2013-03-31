<?php
namespace MichalSpacekCz;

/**
 * Files model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Files extends BaseModel
{

	/** @var \Nette\Http\IRequest */
	protected $httpRequest;


	public function __construct(\Nette\Database\Connection $connection, \Nette\Http\IRequest $httpRequest)
	{
		$this->database = $connection;
		$this->httpRequest = $httpRequest;
	}


	public function getInfo($file)
	{
		$info = new \SplFileInfo($file);
		return ($info->isReadable() ? $info : false);
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
		return $this->database->lastInsertId();
	}


}