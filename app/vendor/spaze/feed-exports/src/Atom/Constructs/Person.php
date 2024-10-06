<?php
declare(strict_types = 1);

namespace Spaze\Exports\Atom\Constructs;

/**
 * Atom person construct.
 *
 * @author Michal Špaček
 */
class Person
{

	public function __construct(
		private string $name,
		private ?string $email = null,
		private ?string $uri = null,
	) {
	}


	public function getName(): string
	{
		return $this->name;
	}


	public function getEmail(): ?string
	{
		return $this->email;
	}


	public function getUri(): ?string
	{
		return $this->uri;
	}

}
