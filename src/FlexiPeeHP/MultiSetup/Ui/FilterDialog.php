<?php

/**
 * broker.dbfinance.cz
 * 
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright (c) 2017-2019, Vítězslav Dvořák
 */

namespace FlexiPeeHP\MultiSetup\Ui;

/**
 * Description of filterDialog
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class FilterDialog extends \Ease\Html\DivTag {

    /**
     * 
     * @param \DBFinance\Engine $engine
     * @param array $properties
     */
    public function __construct($tableId, $columns = []) {
        $columnTabs = new \Ease\TWB4\Tabs(null, ['id' => 'filterTabs']);
        foreach ($columns as $columnProperies) {
            $columnName = $columnProperies['name'];
            $controls = new \Ease\Html\DivTag();
            $currentTab = $columnTabs->addTab($columnProperies['label'], null);
            switch ($columnProperies['type']) {
                case 'checkbox':
                    $controls->addItem(
                            new \Ease\TWB4\FormGroup($columnProperies['label'],
                                    new \Ease\ui\TWBSwitch($columnName, null, null,
                                            ['indeterminate' => true, 'id' => $columnName . 'sw'])));
                    $controls->addItem('&nbsp;' . new \Ease\Html\ATag('#',
                                    '<i class="fas fa-yin-yang"></i>',
                                    ['onClick' => "$('#" . $columnName . "sw').bootstrapSwitch('toggleIndeterminate', true, true); unsetFilterLabel('$columnName'); $('#" . $columnName . "sw').removeClass( 'tablefilter' ); $('#$tableId').DataTable().draw();"]) . '&nbsp;');

                    $this->addJavaScript("
$('#" . $columnName . "sw').on('switchChange.bootstrapSwitch', function(event, state) { setFilterLabel('$columnName'); $('#" . $columnName . "sw').attr('data-type','bool').addClass( 'tablefilter' ).val( function(){ if( $( '#" . $columnName . "sw' ).prop( 'checked' ) ) { return '1' } else { return '0'; } }  );  $('#$tableId').DataTable().draw(); });      
");

                    break;
                case 'datetime':
                    $controls->addItem($columnProperies['label'] . ' ' . _('From'));
                    $controls->addItem(new \Ease\Html\InputDateTag($columnName . '-od',
                                    null,
                                    ['data-column' => $columnName,
                                'data-type' => 'date',
                                'data-end' => 'od',
                                'onChange' => "updateFiltering(this, '$columnName', '$tableId')"
                    ]));

                    $controls->addItem(new \Ease\Html\InputTimeTag($columnName . '-od-time'));

                    $controls->addItem('&nbsp;' . $columnProperies['label'] . ' ' . _('To'));
                    $controls->addItem(new \Ease\Html\InputDateTag($columnName . '-do',
                                    null,
                                    [
                                'data-column' => $columnName,
                                'data-type' => 'date',
                                'data-end' => 'do',
                                'onChange' => "updateFiltering(this, '$columnName', '$tableId')"
                    ]));

                    $controls->addItem(new \Ease\Html\InputTimeTag($columnName . '-do-time'));

                    break;
                case 'date':
                    $controls->addItem($columnProperies['label'] . ' ' . _('From'));
                    $controls->addItem(new \Ease\Html\InputDateTag($columnName . '-od',
                                    null,
                                    ['data-column' => $columnName,
                                'data-type' => 'date',
                                'data-end' => 'od',
                                'onChange' => "updateFiltering(this, '$columnName', '$tableId')"
                    ]));
                    $controls->addItem('&nbsp;' . $columnProperies['label'] . ' ' . _('To'));
                    $controls->addItem(new \Ease\Html\InputDateTag($columnName . '-do',
                                    null,
                                    [
                                'data-column' => $columnName,
                                'data-type' => 'date',
                                'data-end' => 'do',
                                'onChange' => "updateFiltering(this, '$columnName', '$tableId')"
                    ]));
                    break;
                case 'text':
                default:
                    $options = [
                        'style' => 'min-width: 200px',
                        'onChange' => "updateFiltering(this, '$columnName', '$tableId')",
                        'onInput' => "updateFiltering(this, '$columnName', '$tableId')",
                        'onfocusout' => "updateFiltering(this, '$columnName', '$tableId'); $('#$tableId').DataTable().draw();",
                    ];

                    if (array_key_exists('filterby', $columnProperies) && ($columnProperies['filterby'] == 'options')) {
                        $options['data-type'] = 'pillbox-id';
                        $input = new PillBox($columnName,
                                self::idValueNameLabel($columnProperies['options']),
                                '', $options);
                    } else {

                        if (array_key_exists('engine', $columnProperies)) {
                            $dataSource = new $columnProperies['engine'];
                            $filterOptions = self::getFilterOptions($dataSource);

                            if (array_key_exists('filterby', $columnProperies) && ($columnProperies['filterby'] == 'value')) {
                                $filterOptionsRaw = $filterOptions;
                                $filterOptions = [];
                                foreach ($filterOptionsRaw as $option) {
                                    $filterOptions[] = ['id' => $option['name'],
                                        'name' => $option['name']];
                                }
                                $options['data-type'] = 'pillbox-value';
                            } else {
                                $options['data-type'] = 'pillbox-id';
                            }
                            $input = new PillBox($columnName, $filterOptions,
                                    '', $options);
                        } else {
                            $options['data-type'] = 'text';
                            $input = new \Ease\Html\InputTextTag($columnName,
                                    null, $options);
                        }
                    }
                    $controls = new \Ease\TWB4\FormGroup($columnProperies['label'],
                            $input);
                    break;
            }

            $currentTab->addItem($controls);
        }
        parent::__construct(
                new \Ease\TWB4\Form(['name' => 'filter' . $tableId], null, $columnTabs),
                ['id' => 'gridFilter' . $tableId, 'class' => 'filterOptions']);
    }

    /**
     * 
     * @return array
     */
    public static function getFilterOptions($engine) {
        $result = [];
        $candidates = $engine->listingQuery()->orderBy($engine->nameColumn);
        foreach (self::fixIterator($candidates) as $candidat) {
            $engine->setData($candidat);
            $result[] = ['id' => $engine->getMyKey(), 'name' => $name = $engine->getRecordName()];
        }
        return $result;
    }

    /**
     * Always return array
     * 
     * @param \Envms\FluentPDO\Queries\Select $query
     * 
     * @return array
     */
    public static function fixIterator($query) {
        $data = $query->execute();
        return $data ? $data : [];
    }

    public static function idValueNameLabel($optionsRaw) {
        $options = [];
        foreach ($optionsRaw as $option) {
            $options[] = ['id' => $option['value'], 'name' => $option['label']];
        }
        return $options;
    }

    public static function idLabelNameLabel($optionsRaw) {
        $options = [];
        foreach ($optionsRaw as $option) {
            $options[] = ['id' => $option['label'], 'name' => $option['label']];
        }
        return $options;
    }

}
