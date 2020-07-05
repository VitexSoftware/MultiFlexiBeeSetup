<?php

/**
 * Multi FlexiBee Setup - Company Management Class
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 * @copyright  2018-2020 Vitex Software
 */

namespace FlexiPeeHP\MultiSetup;

//use DataTables\Editor,
//    DataTables\Editor\Field,
//    DataTables\Editor\Format,
//    DataTables\Editor\Mjoin,
//    DataTables\Editor\Options,
//    DataTables\Editor\Upload,
//    DataTables\Editor\Validate,
//    DataTables\Editor\ValidateOptions;

/**
 * Description of Engine
 *
 * @author Vítězslav Dvořák <info@vitexsoftware.cz>
 */
class DBEngine extends \Ease\SQL\Engine {

    /**
     *
     * @var \Envms\FluentPDO\Query 
     */
    public $fluent = null;

    /**
     * Filter results by
     * @var array 
     */
    public $filter = null;

    /**
     * Prefill new Record form with values
     * @var array 
     */
    public $defaults = null;

    /**
     *
     * @var string usually id 
     */
    public $keyColumn = 'id';

    /**
     * Column with record name
     * @var string 
     */
    public $nameColumn = null;
    public $createColumn = null; //'created_at';
    public $modifiedColumn = null; //'updated_at';    

    /**
     * Search results targeting to index.php here
     * @var string 
     */
    public $keyword = null;

    /**
     * Object Subject
     * @var string 
     */
    public $subject = null;
    public $newSubmitText;
    public $editSubmitText;
    public $removeSubmitText;
    public $newTitleText;
    public $editTitleText;
    public $removeTitleText;
    public $removeConfirmText;

    /**
     *
     * @var array 
     */
    public $columnsCache = [];

    /**
     *
     * @var array 
     */
    public $initConds = null;

    /**
     *
     * @var string 
     */
    public $detailPage = null;

    /**
     * Data Processing Engine
     * 
     * @param int $init
     * @param int $filter Initial conditons
     */
    public function __construct($init = null, $filter = []) {
        parent::__construct();
        if (is_numeric($init)) {
            $this->loadFromSQL($init);
        } elseif (is_array($init)) {
            $this->takeData($init);
        }
        $this->translate();
        $this->filter = $filter;
        if (is_null($this->detailPage)) {
            $this->setDetailPage();
        }
    }

    public function loadFromSQL($id = null) {
        return $this->takeData($this->getOneRow($id));
    }

    public function takemyTable($myTable) {
        if (is_null($this->subject)) {
            $this->subject = $myTable;
        }
        if (is_null($this->keyword)) {
            $this->keyword = $myTable;
        }
        return parent::takemyTable($myTable);
    }

    /**
     * Set page where to work wiht one row detail
     * 
     * @param string $detailPage
     */
    public function setDetailPage($detailPage = null) {
        $this->detailPage = empty($detailPage) ? ( empty($this->keyword) ? null : $this->keyword . '.php' ) : $detailPage;
    }

    /**
     * Where to look for record name
     * 
     * @return string
     */
    public function getNameColumn() {
        return $this->nameColumn;
    }

    /**
     * 
     */
    public function translate() {
        $this->newSubmitText = sprintf(_('New %s'), $this->subject);
        $this->editSubmitText = sprintf(_('Edit %s'), $this->subject);
        $this->removeSubmitText = sprintf(_('Remove %s'), $this->subject);
        $this->newTitleText = sprintf(_('Create new %s'), $this->subject);
        $this->editTitleText = sprintf(_('Editing %s'), $this->subject);
        $this->removeTitleText = sprintf(_('Removing %s'), $this->subject);
        $this->removeConfirmText = sprintf(_('Are you sure you wish to delete %%d row of %s ?'),
                $this->subject);
    }

    /**
     * 
     * @return string
     */
    public function getRecordName() {
        return $this->getDataValue($this->nameColumn);
    }

    /**
     * Columns Properties
     * 
     * name:   name of column field
     * label:  Text Shown
     * requied: true/false - column is necessary 
     * ro:     true/false - column is readonly
     * hidden: true/false - do not show column
     * column: get foregin content from "table.column"
     * type:   date|datetime|integer|string is default
     * 
     * listingPage: eg.: products.php
     * detailPage:  eg.: product.php
     * idColumn:    column with related id: eg. 'product_id'
     * 
     * @return array
     */
    public function columns($columns = []) {
        if (empty($this->columnsCache)) {

            $columns = \Ease\Functions::reindexArrayBy($columns, 'name');
            foreach ($columns as $columnId => $columnInfo) {
                if (array_key_exists('gdpr', $columnInfo)) {
                    $columns[$columnId]['visible'] = 0;
                }

                if (!array_key_exists('name', $columnInfo)) {
                    $this->addStatusMessage(sprintf(_('Missing ColumnInfo name for %s/%s'),
                                    get_class($this), $columnId));
                }
                if (!array_key_exists('type', $columnInfo)) {
                    $this->addStatusMessage(sprintf(_('Missing ColumnInfo type for %s/%s'),
                                    get_class($this), $columnInfo['name']));
                    $columns[$columnId]['type'] = 'string';
                }
                if (!array_key_exists('valueColumn', $columnInfo)) {
                    $columnInfo['valueColumn'] = $columnInfo['name'];
                }
            }

            if (!array_key_exists('id', $columns)) {
                $columns['id'] = ['name' => 'id', 'type' => 'int', 'hidden' => true,
                    'label' => _('Id')];
            }

            if (isset($this->modifiedColumn) && !array_key_exists($this->modifiedColumn,
                            $columns)) {
                $columns[$this->modifiedColumn] = ['name' => $this->modifiedColumn,
                    'type' => 'datetime',
                    'label' => _('Updated'), 'readonly' => true];
            }

            if (isset($this->createColumn) && !array_key_exists($this->createColumn,
                            $columns)) {
                $columns[$this->createColumn] = ['name' => $this->createColumn, 'type' => 'datetime',
                    'label' => _('Created'), 'readonly' => true];
            }
            $this->columnsCache = $columns;
        }
        return $this->columnsCache;
    }

    /**
     * Skip columns not suposed to be edited
     * 
     * @param array $columnsToFilter
     * 
     * @return array
     */
    public function editableColumns($columnsToFilter) {
        $columnsToEdit = [];
        $keyColum = $this->getKeyColumn();
        foreach ($columnsToFilter as $id => $values) {
            $columnName = $values['name'];
            if (!empty($this->defaults) && array_key_exists($columnName,
                            $this->defaults)) {
                $values['def'] = $this->defaults[$columnName];
            }
            switch ($columnName) {
                case $keyColum:
                case $this->modifiedColumn:
                case $this->createColumn:
                    $values['type'] = 'display';
                    continue 2;
                    break;
                default:
                    $columnsToEdit[] = $values;
                    break;
            }
        }

        return $columnsToEdit;
    }

    /**
     * Get All records
     * 
     * @return array
     */
    public function getAll() {
        return $this->listingQuery()->fetchAll();
    }

    public function getColumnType($colName) {
        return $this->columns()[$colName]['type'];
    }

    /**
     * 
     * @param array $conditions
     * 
     * @return string
     */
    public function getAllForDataTable($conditions = []) {
        $data = [];
        $tableColumns = $this->columns();
        $dtColumns = array_key_exists('columns', $conditions) ? $conditions['columns'] : array_keys($tableColumns);
        unset($conditions['columns']);
        unset($conditions['_']);
        unset($conditions['class']);
        $query = $this->listingQuery();

        $recordsTotal = count($query);

        if ($recordsTotal) {

            if (array_key_exists('search', $conditions) && $conditions['search']['value']) { //All Columns Search
                foreach ($tableColumns as $colProps) {
                    $search = "LIKE '%" . strtolower($conditions['search']['value']) . "%'";
                    $query->whereOr(' LOWER(' . (array_key_exists('column',
                                    $colProps) ? $colProps['column'] : $this->getMyTable() . '.' . "`" . $colProps['name'] . "`") . ') ' . $search);
                }
                unset($conditions['search']);
            }

            foreach ($dtColumns as $column => $colProps) { //One Column search
                if (!empty($colProps['search']['value'])) {

                    if ($this->columnsCache[$colProps['data']]['type'] == 'selectize') {
                        $search = 'IN (' . $colProps['search']['value'] . ')';
                    } else {
                        $search = 'LIKE \'%' . strtolower($colProps['search']['value']) . '%\'';
                    }

                    $query->where(' LOWER(' . (array_key_exists('column',
                                    $colProps) ? $colProps['column'] : $this->getMyTable() . '.' . $colProps['data']) . ') ' . $search);
                }
            }

            foreach ($conditions as $condName => $condValue) {

                switch ($condName) {
                    case 'type':
                    case 'draw':
                    case 'start':
                    case 'length':
                    case 'order':
                    case 'search':
                        break;

                    default:
                        if (array_key_exists($condName, $this->columns())) {
                            $query->where($this->getMyTable() . '.' . $condName,
                                    $condValue);
                        } else {
                            $query->where(str_replace('/', '.', $condName),
                                    $condValue);
                        }
                        break;
                }
            }
        }

        $recordsFiltered = count($query);


        if (array_key_exists('length', $conditions)) {
            $query->limit($conditions['length']);
            unset($conditions['length']);
        }

        if (array_key_exists('start', $conditions)) {
            $query->offset($conditions['start']);
            unset($conditions['start']);
        } else {
            $query->offset('0');
        }

        if (array_key_exists('order', $conditions)) {
            foreach ($conditions['order'] as $order) {
                if ($dtColumns[$order['column']]['searchable'] == 'true') {
                    $colProps = $this->columnsCache[$dtColumns[$order['column']]['data']];

                    $orderBy = array_key_exists('column', $colProps) ? $colProps['column'] : $this->getMyTable() . '.' . $colProps['name'];
                    $orderColumn = array_key_exists('valueColumn', $colProps) ? $colProps['valueColumn'] : $orderBy;

                    $query->orderBy($orderColumn . ' ' . $order['dir']);
                }
            }
            unset($conditions['order']);
        }

        $this->addSelectizeValues($query);

        foreach ($query as $dataRow) {
            $dataRow['DT_RowId'] = 'row_' . $dataRow['id'];
//            $dataRow['DT_RowClass'] = $this->getRowColor($this->checkRow($dataRow));
            $data[] = $this->completeDataRow($dataRow);
        }

        return [
            "draw" => array_key_exists('draw', $conditions) ? intval($conditions['draw']) : 0,
            "recordsTotal" => intval($recordsTotal),
            "recordsFiltered" => intval($recordsFiltered),
            "data" => $data
        ];
    }

    /**
     * Add Select for Selectize IDs values
     * 
     * @param \Envms\FluentPDO $query
     * 
     * @return \Envms\FluentPDO
     */
    public function addSelectizeValues($query) {
        foreach ($this->columns() as $colName => $colProps) {
            if (array_key_exists('valueColumn', $colProps)) {
                $query->select($colProps['valueColumn'] . ' as ' . $colName . '_value');
            }
        }
        return $query;
    }

    /**
     * 
     * @return \Envms\FluentPDO
     */
    public function listingQuery() {
        return $this->getFluentPDO()->from($this->getMyTable());
    }

    /**
     * Return One Row data
     * 
     * @param int $id - Row ID
     * 
     * @return array
     */
    public function getOneRow($id = null) {
        if (is_null($id)) {
            $id = $this->getMyKey();
        }
        return $this->addSelectizeValues($this->listingQuery()->where($this->getMyTable() . '.' . $this->getKeyColumn(),
                                $id))->fetch();
    }

    /**
     * 
     */
    public function editorForm($id = null) {
        if (is_null($id)) {
            $id = $this->getObjectName();
        }
        $newRankForm = new \Ease\Html\DivTag(null, ['id' => $id]);
        foreach ($this->columns() as $column) {
            if (array_key_exists('ro', $column) && ($column['ro'] == true)) {
                continue;
            }
            if (!array_key_exists('type', $column)) {
                $column['type'] = 'string';
            }

            switch ($column['name']) {
                case 'created_at':
                case 'updated_at':
                    break;

                default:
                    switch ($column['type']) {
                        case 'string':
                        default:
                            $newRankForm->addItem(new ui\EditorField($column['name']));
                            break;
                    }

                    break;
            }
        }
        return $newRankForm;
    }

    /**
     * Submited form data validator
     * 
     * @return array
     */
    public function getSaverFields() {
        $saverFields = [];
        foreach ($this->columns() as $column) {
            if ($column['type'] == 'readonly') { //
                continue;
            }
            if ($column['type'] == 'display') { //
                continue;
            }
            if (array_key_exists('column', $column)) { //Joined
                continue;
            }
            $field = Field::inst($column['name']);

            switch ($column['type']) {
                case 'email':
                    $field->validator(Validate::email(ValidateOptions::inst()->message(sprintf(_('%s is not valid email'),
                                                    $column['label']))));

                    break;
                case 'currency':
//                    $field->validator(Validate::numeric(ValidateOptions::inst()->message(sprintf(_('A %s must be a nuber'),
//                                    $column['label']))));
                    break;
//                case 'upload':
//                    echo '';
//                    $field
//                        ->upload(
//                            Upload::inst($_SERVER['DOCUMENT_ROOT'].'/../files/__ID__.__EXTN__')
//                            ->db($this->getMyTable().'_file', $column['name'],
//                                ['filename' => Upload::DB_FILE_NAME,
//                                'created_at' => date('Y-m-d'),
//                                'filesavedas' => md5_file($_FILES['upload']['tmp_name']).'.'.pathinfo($_FILES['upload']['name'],
//                                    PATHINFO_EXTENSION),
//                                $this->getMyTable().'_id' => 0
////                                'fileSize' => Upload::DB_FILE_SIZE
//                            ])
//                        )->setFormatter('Format::nullEmpty');
//                    break;
                default:
                    break;
            }

            if (array_key_exists('requied', $column) && ($column['requied'] == 'true')) {
                $field->validator(Validate::notEmpty(ValidateOptions::inst()->message(sprintf(_('A %s is required'),
                                                $column['label']))));
            }

            if (array_key_exists('unique', $column) && ($column['unique'] == 'true')) {
                $field->validator(Validate::unique(ValidateOptions::inst()->message(sprintf(_('A %s is not unique'),
                                                $column['label']))));
            }



            $saverFields[] = $field;
            /*
              if (array_key_exists('ro', $column) && ($column['ro'] == true)) {
              continue;
              }
              if (!array_key_exists('type', $column)) {
              $column['type'] = 'string';
              }

              switch ($column['name']) {
              case 'created_at':
              case 'updated_at':
              continue;
              break;

              default:
              switch ($column['type']) {
              case 'string':
              default:
              break;
              }

              break;
              }
             */
        }


        /*
          return [
          Field::inst('name')
          ->validator(Validate::notEmpty(ValidateOptions::inst()
          ->message('A name is required')
          )),
          Field::inst('description')
          ->validator(Validate::notEmpty(ValidateOptions::inst()
          ->message('A description is required')
          ))
          ];
         */

        return $saverFields;
    }

    /**
     * 
     * @param array $formPost
     * 
     * @return array
     */
    public function preprocessPost($formPost) {
        if (array_key_exists('action', $formPost) && array_key_exists('data',
                        $formPost)) {
            foreach ($formPost['data'] as $recordId => $recordData) {
                $formPost['data'][$recordId] = $this->prepareToSave($recordData,
                        $formPost['action'], str_replace('row_', '', $recordId));
            }
            unset($_SESSION['feedCache'][get_class($this)]);
        }
        return $formPost;
    }

    /**
     * Vrací data jako HTML.
     *
     * @param array $data
     *
     * @return array
     */
    public function htmlizeData($data) {
        if (is_array($data) && count($data)) {
            $usedCache = array();
            foreach ($data as $rowId => $row) {
                $htmlized = $this->htmlizeRow($row);

                if (is_array($htmlized)) {
                    foreach ($htmlized as $key => $value) {
                        if (!is_null($value)) {
                            $data[$rowId][$key] = $value;
                        } else {
                            if (!isset($data[$rowId][$key])) {
                                $data[$rowId][$key] = $value;
                            }
                        }
                    }
                    if (isset($row['register']) && ($row['register'] == 1)) {
                        $data[$rowId]['name'] = '';
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Vrací řádek dat jako html.
     *
     * @param array $row
     *
     * @return array
     */
    public function htmlizeRow($row) {
        $columns = self::reindexArrayBy($this->columns(), 'name');

        if (is_array($row) && count($row)) {
            foreach ($row as $key => $value) {
                if ($key == str_replace($this->getMyTable() . '.', '',
                                strtolower($this->myKeyColumn))) {
                    continue;
                }
                if (!isset($columns[$key])) {
                    continue;
                }
                if (array_key_exists('type', $columns[$key])) {
                    $fieldType = $columns[$key]['type'];
                } else {
                    $this->addStatusMessage(sprintf(_('Field Type is not set for %s '),
                                    $this->getMyTable(), $key));
                    $fieldType = 'string';
                }

                $fType = preg_replace('/\(.*\)/', '', $fieldType);

                switch ($fType) {
                    case 'hidden':
                        break;

                    case 'boolean':
                        if (is_null($value) || !strlen($value)) {
                            $row[$key] = '<em>NULL</em>';
                        } else {
                            if ($value === '0') {
                                $row[$key] = new \Ease\TWB\GlyphIcon('unchecked');
                            } else {
                                if ($value === '1') {
                                    $row[$key] = new \Ease\TWB\GlyphIcon('check');
                                }
                            }
                        }
                        break;
                    case 'date':
                        if ($value) {
                            $stamper = new \DateTime($value);
                            $row[$key] = new \Ease\Html\ATag('calendar.php?day=' . $stamper->format('Y-m-d'),
                                    strftime("%x", $stamper->getTimestamp()) . ' (' . new ui\ShowTime($stamper->getTimestamp()) . ')');
                        }
                        break;
                    case 'datetime':
                        if ($value) {
                            $stamper = new \DateTime($value);
                            $row[$key] = new \Ease\Html\ATag('calendar.php?day=' . $stamper->format('Y-m-d'),
                                    new \Ease\Html\TimeTag(strftime("%x %r",
                                                    $stamper->getTimestamp()),
                                            ['datetime' => $stamper->getTimestamp()]));
                        }
                        break;
                    default :
                        if (isset($this->keywordsInfo[$key]['refdata']) && strlen(trim($value))) {
                            $table = $this->keywordsInfo[$key]['refdata']['table'];
                            $searchColumn = $this->keywordsInfo[$key]['refdata']['captioncolumn'];
                            $row[$key] = '<a title="' . $table . '" href="search.php?search=' . $value . '&table=' . $table . '&column=' . $searchColumn . '">' . $value . '</a> ' . EaseTWBPart::glyphIcon('search');
                        }
                        if (strstr($key, 'image') && strlen(trim($value))) {
                            $row[$key] = '<img title="' . $value . '" src="logos/' . $value . '" class="gridimg">';
                        }
                        if (strstr($key, 'url')) {
                            $row[$key] = '<a href="' . $value . '">' . $value . '</a>';
                        }

                        break;
                }
            }
        }

        return $row;
    }

    /**
     * 
     * @param type $initialContent
     * 
     * @return type
     */
    public function foterCallback() {
        return null;
    }

    /**
     * @link https://datatables.net/examples/advanced_init/column_render.html 
     * 
     * @return string Column rendering
     */
    public function columnDefs() {
        return '';
    }

    /**
     * 
     * 
     * @param array   $data
     * @param boolean $searchForId
     * 
     * @return int
     */
    public function saveToSQL($data = null, $searchForId = null) {
        if (is_null($data)) {
            $data = $this->getData();
        }
        if ($this->getMyKey($data)) {
            $data = $this->prepareToSave($data, 'edit', $this->getMyKey($data));
        }
        $result = parent::saveToSQL($data, $searchForId);
        $this->finishProcess(null);
        return $result;
    }

    /**
     * By default we do noting
     * 
     * @param array  $data
     * @param string $action   edit|delete|create
     * @param int    $recordId 
     * 
     * @return array
     */
    public function prepareToSave($data, $action, $recordId = null) {
        $now = new \DateTime();
        switch ($action) {
            case 'create':
                if ($this->createColumn) {
                    $data[$this->createColumn] = date('Y-m-d H:i:s');
                }
                if ($this->modifiedColumn) {
                    unset($data[$this->modifiedColumn]);
                }
                break;
            case 'edit':
                if ($this->createColumn) {
                    unset($data[$this->createColumn]);
                }
                if ($this->modifiedColumn) {
                    $data[$this->modifiedColumn] = date('Y-m-d H:i:s');
                }
                break;

            default:
                break;
        }

        return $data;
    }

    /**
     * Vyhledavani v záznamech objektu
     *
     * @param string $what hledaný výraz
     * 
     * @return array pole výsledků
     */
    public function searchString($what) {
        $results = [];
        $conds = [];
        $what = str_replace('.', '\.', $what);
        $columns[] = $this->getMyTable() . '.' . $this->getKeyColumn();
        foreach ($this->getSqlColumns() as $keyword => $keywordInfo) {
            if (strstr($keywordInfo['type'], 'text')) {
                if (array_key_exists('column', $keywordInfo)) {
                    $conds[] = $keywordInfo['column'] . " LIKE '%" . $what . "%'";
                } else {
                    $conds[] = $this->getMyTable() . ".`$keyword` LIKE '%" . $what . "%'";
                }
            }
        }
        if (count($conds)) {
            $found = $this->listingQuery()->where('(' . implode(' OR ', $conds) . ')');

            foreach (self::fixIterator($found) as $result) {
                $this->setData($result);
                $occurences = '';
                foreach ($result as $key => $value) {
                    if (strstr($value, stripslashes($what))) {
                        $occurences .= '(' . $key . ': ' . $value . ') ';
                    }
                }
                $results[$result[$this->keyColumn]] = [$this->nameColumn => $this->getRecordName(),
                    'what' => $occurences, 'url' => $this->getUrlForRecord($result['id'])];
            }
        }
        return $results;
    }

    public function getSqlColumns() {
        $sqlColumns = [];
        foreach ($this->columns() as $columnInfo) {
            if (array_key_exists('virtual', $columnInfo)) {
                continue;
            }
            switch ($columnInfo['type']) {
                case '':
                    break;
                default :
                    $sqlColumns[$columnInfo['name']] = $columnInfo;
                    break;
            }
        }
        return $sqlColumns;
    }

    /**
     * 
     * @return array
     * 
     * @throws \Exception
     */
    public function getGetDataTableColumns() {
        $dataTableColumns = [];
        foreach ($this->columns() as $columnInfo) {
            if (array_key_exists('hidden', $columnInfo) && ($columnInfo['hidden'] == true)) {
                continue;
            }

            if (!array_key_exists('type', $columnInfo)) {
                throw new \Exception(sprintf(_('Column "%s" of %s without type definition'),
                                $columnInfo['name'], get_class($this)));
            }

            switch ($columnInfo['type']) {
//                case '':
//                    break;
                default :
                    unset($columnInfo['column']);
                    $dataTableColumns[$columnInfo['name']] = $columnInfo;
                    break;
            }
        }
        return array_values($dataTableColumns);
    }

    /**
     * pre code for DataTable initialization
     * 
     * @param string $tableID css #id of table
     * 
     * @return string
     */
    public function preTableCode($tableID) {
        return '';
    }

    /**
     * Additional code for DataTable initialization
     * 
     * @param string $tableID css #id of table
     * 
     * @return string
     */
    public function tableCode($tableID) {
        return '';
    }

    /**
     * post code for DataTable initialization
     * 
     * @param string $tableID css #id of table
     * 
     * @return string
     */
    public function postTableCode($tableID) {
        return '';
    }

    /**
     * 
     */
    public function feedSelectize($options = []) {
        $result = [];
        $candidates = $this->listingQuery();
        foreach (self::fixIterator($candidates) as $candidat) {
            $result[] = ['label' => $candidat[$this->getNameColumn()], 'value' => intval($candidat[$this->getKeyColumn()])];
        }
        return $result;
    }

    /**
     * Save selectize records to cache
     * 
     * @param array $options
     * 
     * @return array
     */
    public function feedSelectizeCached($options = []) {
        if (!isset($_SESSION['feedCache'][get_class($this)]) || empty($_SESSION['feedCache'][get_class($this)])) {
            $_SESSION['feedCache'][get_class($this)] = $this->feedSelectize($options);
        }
        return $_SESSION['feedCache'][get_class($this)];
    }

    /**
     * 
     * @return array
     */
    public function getFilterOptions() {
        $result = [];
        $candidates = $this->listingQuery()->orderBy($this->nameColumn);
        foreach (self::fixIterator($candidates) as $candidat) {
            $this->setData($candidat);
            $result[] = ['id' => $this->getMyKey(), 'name' => $name = $this->getRecordName()];
        }
        return $result;
    }

    /**
     * 
     */
    public function getHiddenTagets($extra = []) {
        $hiddenColumns = [];
        foreach (array_values($this->columns()) as $columnId => $columnInfo) {
            if (array_key_exists('hidden', $columnInfo) && ($columnInfo['hidden'] == true)) {
                $hiddenColumns[] = $columnId;
            }
        }
        return $hiddenColumns;
    }

    /**
     * 
     */
    static function selectize($rawdata) {
        $selectized = [];
        foreach ($rawdata as $key => $value) {
            $selectized[] = ['label' => $value, 'value' => $key];
        }
        return $selectized;
    }

    public function getCustomerID() {
        return $this->getDataValue('client_id');
    }

    public function getResume() {
        return implode(' ', $this->getData());
    }

    public function postCreate($datableSaver, $id, $row) {
        
    }

    public function validation($saver) {
        
    }

    public function editorOpenJS() {
        return '';
    }

    public function editorPostCreateJS() {
        return '';
    }

    public function editorCreateJS() {
        return '';
    }

    public function editorSubmitCompleteJS() {
        return '';
    }

    public static function renderYesNo($columns) {
        return '
            {
                "render": function ( data, type, row ) {
                    if(data == "1") { return  "' . _('Yes') . '" } else { return "' . _('No') . '" };
                },
                "targets": [' . $columns . ']
            },
        ';
    }

    /**
     * 
     * 
     * @param string $columns
     * 
     * @return string
     */
    public static function renderSelectize($columns) {
        return '
            {
                "render": function ( data, type, row, opts ) {
                    opts.settings.aoColumns[ opts.col ].options.forEach(function(element) {
                        if(element[\'value\'] ==  data){
                            data = \'<a href="\' + opts.settings.aoColumns[ opts.col ].detailPage + \'?id=\'+ data + \'">\' + element[\'label\'] + \'</a>\';
                        }
                    });
                    return data;
                },
                "targets": [' . $columns . ']
            },
            
';
    }

    /**
     * 
     * 
     * @param string $columns
     * 
     * @return string
     */
    public static function renderSelectized($columns) {
        return '
            {
                "render": function ( data, type, row, opts ) {
                    data = \'<a href="\' + opts.settings.aoColumns[ opts.col ].detailPage + \'?id=\'+ data + \'">\' + element[\'label\'] + \'</a>\';
                    return data;
                },
                "targets": [' . $columns . ']
            },
            
';
    }

    /**
     * Ad Link to detail page on 
     * 
     * @param string $columns
     * 
     * @return string
     */
    public static function renderIdLink($columns) {
        return '
            {
                "render": function ( data, type, row, opts ) { return renderIdLink( data, type, row, opts ); }, 
                "targets": [' . $columns . ']
            },
            
';
    }

    public static function renderDate($columns, $target = 'calendar.php') {
        return '
            {
                "render": function ( data, type, row ) {
                    if (type == "sort" || type == \'type\'){
                        return data;            
                    }
                    dataRaw = data;
                    if (data) { 
                        data.replace(/(\d{4})-(\d{1,2})-(\d{1,2})/, function(match,y,m,d) { data = d + \'. \' + m + \'. \' + y; });
                    } else data = "";
                    return  "<a href=\"' . $target . '?day=" + dataRaw +"\"><time datetime=\"" + dataRaw + "\">" + data + "</time></a>";
                },
                "targets": [' . $columns . ']
            },
            ';
    }

    public static function renderDocumentLink($columns, $target = 'document.php') {
        return '
            {
                "render": function ( data, type, row ) {
                    if (type == "sort" || type == \'type\'){
                        return data;            
                    }
                    var apiUrlParts = data.split("/")
                    var evidence = apiUrlParts[5];
                    if(apiUrlParts.length > 6){
                        var docid    = apiUrlParts[6]
                        if(evidence == "adresar"){
                            return  "<a href=\"adresar.php?id=" + docid +"&evidence=" + evidence + "\" target=\"_blank\" >" + decodeURIComponent(docid.replace("code:","")) + "</a>";
                        } else {
                            return  "<a href=\"' . $target . '?id=" + docid +"&evidence=" + evidence + "\" target=\"_blank\" >" + decodeURIComponent(docid.replace("code:","")) + "</a>";
                        }
                    } else {
                        return  data;
                    }
                },
                "targets": [' . $columns . ']
            },
            ';
    }

    public static function renderDismisButton($columns, $target = 'dismis.php') {
        return '
            {
                "render": function ( data, type, row ) {
                    if (type == "sort" || type == \'type\'){
                        return data;            
                    }
                    dataRaw = data;
                    if (data == "0000-00-00 00:00:00") { 
                        return  "<button class=\"dismis\" onclick=\"dismisLog(" + row["id"] + ", this )\">' . _('Dismis') . '</button>";
                    } else {
                        data.replace(/(\d{4})-(\d{1,2})-(\d{1,2})/, function(match,y,m,d) { data = d + \'. \' + m + \'. \' + y; });
                        return  "<a href=\"' . $target . '?day=" + dataRaw +"\"><time datetime=\"" + dataRaw + "\">" + data + "</time></a>";
                    }
                },
                "targets": [' . $columns . ']
            },
            ';
    }

    public function getColumnsOfType($types) {
        $columns = [];
        $columnsRaw = $this->columns();
        if (!is_array($types)) {
            $types = [$types];
        }
        foreach ($types as $type) {
            foreach ($columnsRaw as $columnName => $columnInfo) {
                if ($columnInfo['type'] == $type) {
                    $columns[$columnName] = $columnInfo;
                }
            }
        }
        return $columns;
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

    public function getUrlForRecord($recordID) {
        return isset($this->detailPage) ? $this->detailPage . '?id=' . $recordID : null;
    }

    public function getAttachments() {
        $attachments = $this->getFluentPDO()->from($this->keyword . '_file')->where($this->keyword . '_id',
                        $this->getMyKey())->fetchAll();
        return empty($attachments) ? [] : $attachments;
    }

    /**
     * Confirm data save ability
     * 
     * @param array $dataToSave
     * 
     * @return boolean
     */
    public function presaveCheck($dataToSave) {
        $this->loadFromSQL();
        return true;
    }

    /**
     * Finish Work
     * 
     * @param DataTableSaver $saver
     * 
     * @return DataTableSaver
     */
    public function finishProcess($saver) {

        $_SESSION['feedCache'][is_null($saver) ? get_class($this) : get_class($saver->engine)] = $this->feedSelectize([]);

        $out = [];
        $dataRaw = $saver->data();
        foreach ($dataRaw['data'] as $id => $outline) {
            $out[$id] = $this->completeDataRow($outline);
        }
        $dataRaw['data'] = $out;
        $saver->setData($dataRaw);
        return $saver;
    }

    /**
     * Check for record sanity
     * 
     * @param array $dataRows
     * 
     * @return string
     */
    public function checkRow($dataRows) {
        return null;
    }

    /**
     * Convert checkRow result to css class name for datatable row
     * 
     * @param type $checkRowResult
     * 
     * @return string css class name(s)
     */
    public function getRowColor($checkRowResult) {
        return null;
    }

    public function completeDataRow(array $dataRowRaw) {
        foreach ($this->columnsCache as $colName => $colProps) {
            if (array_key_exists($colName . '_value', $dataRowRaw)) {
                $dataRowRaw[$colName . '_id'] = $dataRowRaw[$colName];
                $dataRowRaw[$colName] = $dataRowRaw[$colName . '_value'];
                unset($dataRowRaw[$colName . '_value']);
            }

            if (array_key_exists('detailPage', $colProps) && array_key_exists('idColumn',
                            $colProps) && array_key_exists($colName . '_id', $dataRowRaw)) {
                $dataRowRaw[$colName] = '<a href="' . $colProps['detailPage'] . '?id=' . $dataRowRaw[$colName . '_id'] . '" class="alert-link text-light">' . $dataRowRaw[$colName] . '</a>';
            }
        }
        return $dataRowRaw;
    }

    public function getColumnInfo($columnName) {
        return array_key_exists($columnName, $this->columnsCache) ? $this->columnsCache[$columnName] : null;
    }

}
