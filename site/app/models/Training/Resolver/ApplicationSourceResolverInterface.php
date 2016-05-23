<?php
namespace MichalSpacekCz\Training\Resolver;

/**
 * Training application source resolver interface.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
interface ApplicationSourceResolverInterface
{
	public function isTrainingApplicationOwner($note);
}
