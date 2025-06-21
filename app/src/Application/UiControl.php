<?php
declare(strict_types = 1);

namespace MichalSpacekCz\Application;

use MichalSpacekCz\Templating\DefaultTemplate;
use Nette\Application\UI\Control;

/**
 * @property-read DefaultTemplate $template To suppress PhpStorm's "Member has private visibility but can be accessed via '__get' magic method" in child classes
 */
abstract class UiControl extends Control
{
}
