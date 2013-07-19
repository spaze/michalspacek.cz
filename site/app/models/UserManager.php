<?php
namespace MichalSpacekCz;

/**
 * UserManager model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class UserManager extends BaseModel
{

	const KNOCK_KNOCK = 'knockKnock';


	public function verifySignInAuthorization($knockKnock)
	{
		if ($knockKnock != self::KNOCK_KNOCK) {
			throw new \Nette\Application\BadRequestException("Knock, knock. Who's there? GTFO!", \Nette\Http\Response::S404_NOT_FOUND);
		}
	}


}