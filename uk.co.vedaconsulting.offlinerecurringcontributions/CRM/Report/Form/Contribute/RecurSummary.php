<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Report_Form_Contribute_RecurSummary extends CRM_Report_Form {
  /**
   */
  public function __construct() {

    $this->_columns = array(
      'civicrm_contribution_recur' => array(
        'dao' => 'CRM_Contribute_DAO_ContributionRecur',
        'fields' => array(
          'id' => array(
            'no_display' => TRUE,
            'required' => TRUE,
          ),
          'payment_instrument_id' => array(
            'title' => ts('Payment Instrument'),
            'default' => TRUE,
            'required' => TRUE,
          ),
          'start_date' => array(
            'title' => ts('Started'),
            'default' => TRUE,
            'required' => TRUE,
          ),
          'cancel_date' => array(
            'title' => ts('Cancelled'),
            'default' => TRUE,
            'required' => TRUE,
          ),
          'contribution_status_id' => array(
            'title' => ts('Active'),
            'default' => TRUE,
            'required' => TRUE,
          ),
          'amount' => array(
            'title' => ts('Total Amount'),
            'default' => TRUE,
            'required' => TRUE,
          ),
        ),
        'filters' => array(
          'start_date' => array(
            'title' => ts('Start Date'),
            'operatorType' => CRM_Report_Form::OP_DATE,
            'type' => CRM_Utils_Type::T_TIME,
          ),
        ),
      ),
    );
    $this->_currencyColumn = 'civicrm_contribution_recur_currency';
    parent::__construct();
  }

  public function preProcess() {
    $this->addFormRule(array('CRM_Report_Form_Contribute_RecurSummary', 'formRule'), $this);
    parent::preProcess();
  }
  
	/**
   * @param bool $freeze
   *
   * @return array
   */
  public function setDefaultValues($freeze = TRUE) {
    return parent::setDefaultValues($freeze);
  }

  public function select() {
    $select = array();
    $this->_columnHeaders = array();
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('group_bys', $table)) {
        foreach ($table['group_bys'] as $fieldName => $field) {
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
          if (!empty($this->_params['group_bys'][$fieldName])) {
            switch (CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq'])) {
              case 'YEARWEEK':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";
                $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Week';
                break;

              case 'YEAR':
                $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Year';
                break;

              case 'MONTH':
                $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Month';
                break;

              case 'QUARTER':
                $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                $field['title'] = 'Quarter';
                break;
            }
            if (!empty($this->_params['group_bys_freq'][$fieldName])) {
              $this->_interval = $field['title'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = $field['title'] . ' Beginning';
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type'] = $field['type'];
              $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = $this->_params['group_bys_freq'][$fieldName];

              // just to make sure these values are transfered to rows.
              // since we need that for calculation purpose,
              // e.g making subtotals look nicer or graphs
              $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = array('no_display' => TRUE);
              $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = array('no_display' => TRUE);
            }
          }
        }
      }

      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if ($tableName == 'civicrm_address') {
            $this->_addressField = TRUE;
          }
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {

            // only include statistics columns if set
            if (!empty($field['statistics'])) {
              foreach ($field['statistics'] as $stat => $label) {
                switch (strtolower($stat)) {
                  case 'sum':
                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'count':
                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;

                  case 'avg':
                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                    break;
                }
              }
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value('title', $field);
            }
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($params, $files, $self) {
    $errors = array();
	// GK15082017-  FIX ME:  Not sure about the purpose of this rule. Fixing this in an assumption that this has been added to force the user to select start & end date, if Custom Date Range filter option selected.
	if ($params['start_date_relative'] == '0' && (empty($params['start_date_from']) && empty($params['start_date_to']))) {
      $errors['_qf_default'] = ts("Please select date range in filter.");
    }
    return $errors;
  }

  public function from() {
    $softCreditJoin = "LEFT";
    if (!empty($this->_params['fields']['soft_amount']) &&
      empty($this->_params['fields']['total_amount'])
    ) {
      // if its only soft credit stats, use inner join
      $softCreditJoin = "INNER";
    }

    $this->_from = "
             FROM civicrm_contribution_recur   {$this->_aliases['civicrm_contribution_recur']}
    ";
  }

  public function postProcess() {
    $this->beginPostProcess();
    $sql = $this->buildQuery(TRUE);
    $rows = array();

		$this->buildRows($sql, $rows);
    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  public function groupBy() {
    $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contribution_recur']}.payment_instrument_id";
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
     
    $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument();

    $entryFound = FALSE;

    $startDateFrom = $startDateTo = '';
		
		if (empty($_POST)) {
			$defaults = $this->getVar('_defaults');
		}
		
		if (isset($_POST['start_date_from']) && !empty($_POST['start_date_from'])) {
      $startDateFrom = CRM_Utils_Date::processDate($_POST['start_date_from']);
    } else {
			$startDateFrom = CRM_Utils_Date::processDate($defaults['start_date_from']);
		}

    if (isset($_POST['start_date_to']) && !empty($_POST['start_date_to'])) {
      $startDateTo = CRM_Utils_Date::processDate($_POST['start_date_to']);
    }  else {
			$startDateTo = CRM_Utils_Date::processDate($defaults['start_date_to']);
		}
    
    $started = $cancelled = $active = $total = 0;

    foreach ($rows as $rowNum => $row) {
      
      $paymentInstrumentId = CRM_Utils_Array::value('civicrm_contribution_recur_payment_instrument_id', $row);

      $rows[$rowNum]['civicrm_contribution_recur_start_date'] = 0;
      $rows[$rowNum]['civicrm_contribution_recur_cancel_date'] = 0;
      $rows[$rowNum]['civicrm_contribution_recur_contribution_status_id'] = 0;
			
      $startedSql = "SELECT count(*) as count FROM civicrm_contribution_recur WHERE payment_instrument_id = $paymentInstrumentId";
      if (!empty($startDateFrom)) {
        $startedSql .= " AND start_date >= '{$startDateFrom}'";
      }
      if (!empty($startDateTo)) {
        $startedSql .= " AND start_date <= '{$startDateTo}'";
      }      
    
      $startedDao = CRM_Core_DAO::executeQuery($startedSql);
      $startedDao->fetch();

      $rows[$rowNum]['civicrm_contribution_recur_start_date'] = $startedDao->count;
      $started = $started + $startedDao->count;

      $cancelledSql = "SELECT count(*) as count FROM civicrm_contribution_recur WHERE payment_instrument_id = $paymentInstrumentId";
      if (!empty($startDateFrom)) {
        $cancelledSql .= " AND cancel_date >= '{$startDateFrom}'";
      }
      if (!empty($startDateTo)) {
        $cancelledSql .= " AND cancel_date <= '{$startDateTo}'";
      }
      
      $cancelledDao = CRM_Core_DAO::executeQuery($cancelledSql);
      $cancelledDao->fetch();

      $rows[$rowNum]['civicrm_contribution_recur_cancel_date'] = $cancelledDao->count;

      $cancelled = $cancelled + $cancelledDao->count;

      $activeSql = "SELECT count(*) as count FROM civicrm_contribution_recur WHERE payment_instrument_id = $paymentInstrumentId";
      if (!empty($startDateFrom)) {
        //$activeSql .= " AND start_date >= '{$startDateFrom}'";
        //$activeSql .= " AND end_date >= '{$startDateFrom}'";
      }
      if (!empty($startDateTo)) {
        $activeSql .= " AND start_date <= '{$startDateTo}'";
        $activeSql .= " AND (end_date >= '{$startDateTo}' OR end_date IS NULL)";
				$activeSql .= " AND (cancel_date > '{$startDateTo}' OR cancel_date IS NULL)";// http://support.vedaconsulting.co.uk/issues/701
      }
			
      $activeDao = CRM_Core_DAO::executeQuery($activeSql);
      $activeDao->fetch();

      $rows[$rowNum]['civicrm_contribution_recur_contribution_status_id'] = $activeDao->count;

      $active = $active + $activeDao->count;
      
    
      
      $amountSql = "
	SELECT SUM(cc.total_amount) as amount FROM `civicrm_contribution` cc 
	INNER JOIN civicrm_contribution_recur cr ON (cr.id = cc.contribution_recur_id AND cr.payment_instrument_id = {$paymentInstrumentId}) 
	WHERE cc.contribution_status_id = 1 AND cc.is_test = 0";
      if (!empty($startDateFrom)) {
        $amountSql .= " AND cc.`receive_date` >= '{$startDateFrom}'";
      }
      if (!empty($startDateTo)) {
        $amountSql .= " AND cc.`receive_date` <= '{$startDateTo}'";
      }      

      $amountDao = CRM_Core_DAO::executeQuery($amountSql);
      $amountDao->fetch();

      $rows[$rowNum]['civicrm_contribution_recur_amount'] = CRM_Utils_Money::format($amountDao->amount);

      $total = $total + $amountDao->amount;

      // handle payment instrument id
      if ($value = CRM_Utils_Array::value('civicrm_contribution_recur_payment_instrument_id', $row)) {
        $rows[$rowNum]['civicrm_contribution_recur_payment_instrument_id'] = $paymentInstruments[$value];
        $entryFound = TRUE;
      }

      //$entryFound = $this->alterDisplayAddressFields($row, $rows, $rowNum, 'contribute/detail', 'List all contribution(s) for this ') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }

    $lastRow = array('civicrm_contribution_recur_payment_instrument_id' => '',
                    'civicrm_contribution_recur_start_date' => $started,
                    'civicrm_contribution_recur_cancel_date' => $cancelled,
                    'civicrm_contribution_recur_contribution_status_id' => $active,
                    'civicrm_contribution_recur_amount' => CRM_Utils_Money::format($total),
    );
    $rows[] = $lastRow;
  }
  
}
