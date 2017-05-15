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

	/** @var string */
	protected $email;

	/** @var string */
	protected $uri;


	/**
	 * Person constructor.
	 *
	 * @param string $name
	 * @param string|null $email
	 * @param string|null $uri
	 */
	public function __construct(string $name, ?string $email = null, ?string $uri = null)
	{
		$this->name = $name;
		$this->email = $email;
		$this->uri = $uri;
	}


	/**
	 * Get person's name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}


	/**
	 * Get person's email.
	 *
	 * @return string|null
	 */
	public function getEmail(): ?string
	{
		return $this->email;
	}


	/**
	 * Get person's URI.
	 *
	 * @return string|null
	 */
	public function getUri(): ?string
	{
		return $this->uri;
	}

}
