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

	/** @var string */
	protected $name;

	/** @var string|null */
	protected $email;

	/** @var string|null */
	protected $uri;


	public function __construct(string $name, ?string $email = null, ?string $uri = null)
	{
		$this->name = $name;
		$this->email = $email;
		$this->uri = $uri;
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
