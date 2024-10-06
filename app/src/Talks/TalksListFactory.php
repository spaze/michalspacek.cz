<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Talks;

interface TalksListFactory
{

	public function create(): TalksList;

}
