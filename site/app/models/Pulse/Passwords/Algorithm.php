<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Pulse\Passwords;

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

	/** @var \DateTime */
	public $from;

	/** @var boolean */
	public $fromConfirmed;

	/** @var \stdClass */
	public $params;

	/** @var string */
	public $fullAlgo;

	/** @var \DateTime|null */
	public $latestDisclosure;

	/** @var array */
	public $disclosures = array();

	/** @var array */
	public $disclosureTypes = array();

	/** @var string */
	public $note;

}
