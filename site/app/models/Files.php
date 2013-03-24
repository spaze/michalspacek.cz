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


	public function getInfo($file)
	{
		$info = new \SplFileInfo($file);
		return ($info->isReadable() ? $info : false);
	}


	public function logDownload($id, $ipAddress, $userAgent)
	{
		$datetime = new \DateTime();
		$this->database->query('INSERT INTO file_downloads', array(
			'key_file'      => $id,
			'ip'            => $ipAddress,
			'user_agent'    => $userAgent,
			'time'          => $datetime,
			'time_timezone' => $datetime->getTimezone()->getName(),
		));
	}


}