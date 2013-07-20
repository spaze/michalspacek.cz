<?php
/**
 * A forbidden presenter.
 *
 * Does not extend BasePresenter to avoid loop in startup().
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class ForbiddenPresenter extends \Nette\Application\UI\Presenter
{
}
