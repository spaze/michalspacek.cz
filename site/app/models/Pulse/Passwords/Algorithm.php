<?php
namespace MichalSpacekCz\Pulse\Passwords;

/**
 * Pulse passwords algorithm object.
 *
 * @author Michal Špaček
 * @package pulse.michalspacek.cz
 */
class Algorithm
{

	/** @var integer */
	public $id;

	/** @var string */
	public $alias;

	/** @var \DateTime */
	public $from;

	/** @var \stdClass */
	public $params;

	/** @var string */
	public $fullAlgo;

	/** @var array */
	public $disclosures = array();

	/** @var array */
	public $disclosureTypes = array();

}
