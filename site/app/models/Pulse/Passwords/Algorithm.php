<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

use DateTime;
use stdClass;

class Algorithm
{

	/** @var integer */
	public $id;

	/** @var string */
	public $alias;

	/** @var boolean */
	public $salted;

	/** @var boolean */
	public $stretched;

	/** @var DateTime */
	public $from;

	/** @var boolean */
	public $fromConfirmed;

	/** @var stdClass */
	public $params;

	/** @var string */
	public $fullAlgo;

	/** @var DateTime|null */
	public $latestDisclosure;

	/** @var stdClass[] */
	public $disclosures = array();

	/** @var boolean[] */
	public $disclosureTypes = array();

	/** @var string */
	public $note;

}
