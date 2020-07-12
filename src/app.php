<?php

/**
 * Multi FlexiBee Setup - Customer instance editor.
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2017-2020 Vitex Software
 */

namespace FlexiPeeHP\MultiSetup\Ui;

use Ease\TWB4\LinkButton;
use Ease\TWB4\Panel;
use Ease\TWB4\Row;
use FlexiPeeHP\MultiSetup\Application;
use FlexiPeeHP\MultiSetup\Ui\PageBottom;
use FlexiPeeHP\MultiSetup\Ui\PageTop;
use FlexiPeeHP\MultiSetup\Ui\RegisterAppForm;

require_once './init.php';


$oPage->onlyForLogged();

$oPage->addItem(new PageTop(_('Application')));

$apps = new Application($oPage->getRequestValue('id', 'int'));
$instanceName = $apps->getDataValue('nazev');

if ($oPage->isPosted()) {
    if ($apps->takeData($_POST) && !is_null($apps->saveToSQL())) {
        $apps->addStatusMessage(_('Application Saved'), 'success');
//        $apps->prepareRemoteFlexiBee();
        $oPage->redirect('?id=' . $apps->getMyKey());
    } else {
        $apps->addStatusMessage(_('Error saving Application'), 'error');
    }
}

if (strlen($instanceName)) {
    $instanceLink = '';
} else {
    $instanceName = _('New Application');
    $instanceLink = null;
}

$instanceRow = new Row();
$instanceRow->addColumn(8, new RegisterAppForm($apps));

$instanceRow->addColumn(4, new \Ease\Html\ImgTag(empty($apps->getDataValue('image')) ? 'images/apps.svg' : $apps->getDataValue('image'),'Logo',['class'=>'img-fluid']));


$oPage->container->addItem(new Panel($instanceName, 'info',
                $instanceRow,
                is_null($apps->getMyKey()) ?
                        new LinkButton('', _('Config fields'), 'inverse disabled') :
                        [new LinkButton('conffield.php?app_id=' . $apps->getMyKey(), _('Config fields'), 'warning'),
                    new ConfigFieldsBadges(\FlexiPeeHP\MultiSetup\Conffield::getAppConfigs($apps->getMyKey()))
                        ]
));

$oPage->addItem(new PageBottom());

$oPage->draw();
