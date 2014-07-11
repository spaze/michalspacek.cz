<?php
namespace MichalSpacekCz;

/**
 * DummyTranslator service.
 *
 * @author     Michal Špaček
 * @package    michalspacek.cz
 */
class DummyTranslator implements \Nette\Localization\ITranslator
{


    public function translate($message, $count = null)
    {
        return $message;
    }


}