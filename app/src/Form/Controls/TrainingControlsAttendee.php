<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Controls\TextInput;

final readonly class TrainingControlsAttendee
{

	public function __construct(
		private TextInput $attendeeName,
		private TextInput $email,
	) {
	}


	public function getAttendeeName(): TextInput
	{
		return $this->attendeeName;
	}


	public function getEmail(): TextInput
	{
		return $this->email;
	}

}
