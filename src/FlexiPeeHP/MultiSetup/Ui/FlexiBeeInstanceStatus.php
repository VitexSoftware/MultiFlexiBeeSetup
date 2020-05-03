<?php

/**
 * Multi FlexiBee Setup  - FlexiBee server companys status
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2015-2020 Vitex Software
 */

namespace FlexiPeeHP\MultiSetup\Ui;

/**
 * Description of FlexiBeeInstanceStatus
 *
 * @author vitex
 */
class FlexiBeeInstanceStatus extends \Ease\Html\TableTag {

    public function __construct($flexiBees, $properties = array()) {
        $properties['class'] = 'table';
        parent::__construct(null, $properties);

        $this->addRowHeaderColumns([_('Code'), _('Name'), _('Show'), _('State'), _('watching Changes'), '']);

        $companer = new \FlexiPeeHP\MultiSetup\Company();
        $registered = $companer->getColumnsFromSQL(['id', 'company'], ['flexibee' => $flexiBees->getMyKey()], 'id', 'company');

        foreach ($this->companys($flexiBees->getData()) as $companyData) {
            $setter = new \FlexiPeeHP\Nastaveni(1, array_merge($flexiBees->getData(), ['company' => $companyData['dbNazev']]));
            $companyDetail = $setter->getData();

            $registerParams = [
                'company' => $companyData['dbNazev'],
                'nazev' => $companyData['nazev'],
                'ic' => array_key_exists('ic', $companyDetail) ? $companyDetail['ic'] : '',
                'email' => array_key_exists('email', $companyDetail) ? $companyDetail['email'] : '',
            ];

            unset($companyData['id']);
            unset($companyData['licenseGroup']);
            unset($companyData['createDt']);

            $companyData['action'] = array_key_exists($companyData['dbNazev'], $registered) ? new \Ease\TWB4\LinkButton('company.php?id=' . $registered[$companyData['dbNazev']]['id'], _('Edit'), 'success') : new \Ease\TWB4\LinkButton('company.php?' . http_build_query($registerParams), _('Register'), 'warning');
            $this->addRowColumns($companyData);
        }
    }

    public function companys($serverAccess) {
        $companer = new \FlexiPeeHP\Company(null, $serverAccess);
        $companys = $companer->getAllFromFlexibee();
        return empty($companys) ? [] : $companys;
    }

}