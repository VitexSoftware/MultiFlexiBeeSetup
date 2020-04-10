<?php
/**
 * Multi FlexiBee Setup - Index page.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2020 Vitex Software
 */

namespace FlexiPeeHP\MultiSetup\Ui;

use Ease\Html\ATag;
use Ease\TWB4\Panel;
use Ease\TWB4\Row;
use FlexiPeeHP\MultiSetup\FlexiBees;

require_once './init.php';

$oPage->addItem(new PageTop(_('Multi FlexiBee Setup')));

if(empty($oUser->listingQuery()->count())){
    $oUser->addStatusMessage(_('There is no administrators in the database.'),'warning');
    $oPage->container->addItem(new \Ease\TWB4\LinkButton('createaccount.php', _('Create first Administrator Account'), 'success'));
}



$oPage->addItem(new PageBottom());

$oPage->draw();
