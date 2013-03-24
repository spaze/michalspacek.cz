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


}