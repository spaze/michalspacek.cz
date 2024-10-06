<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Articles\Components;

interface ArticleWithId
{

	public function hasId(): bool;


	public function getId(): ?int;

}
