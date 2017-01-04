<?php
namespace MichalSpacekCz\Training\Resolver;

use Nette\Utils\Strings;

/**
 * Vrana training resolver model.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class Vrana implements ApplicationSourceResolverInterface
{

	/**
	 * Is this training application ours?
	 *
	 * @param string $note
	 * @return boolean
	 */
	public function isTrainingApplicationOwner($note)
	{
		return (Strings::contains(Strings::lower($note), 'jakub vrána'));
	}

}
