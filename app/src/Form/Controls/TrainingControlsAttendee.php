<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Form\Controls;

use Nette\Forms\Controls\TextInput;

final readonly class TrainingControlsAttendee
{

	public function __construct(
		private TextInput $name,
		private TextInput $email,
	) {
	}


	public function getName(): TextInput
	{
		return $this->name;
	}


	public function getEmail(): TextInput
	{
		return $this->email;
	}

}
