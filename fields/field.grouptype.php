<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	class FieldGroupType extends Field {
		protected $_driver = null;
		
	/*-------------------------------------------------------------------------
		Field definition:
	-------------------------------------------------------------------------*/
		
		public function __construct(&$parent) {
			parent::__construct($parent);
			
			$this->_name = 'Group Type';
			$this->_driver = $this->_engine->ExtensionManager->create('frontendmembermanager');
			
			// Set defaults:
			$this->set('show_column', 'no');
		}
		
		public function createTable() {
			$field_id = $this->get('id');
			
			return $this->_engine->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`value` enum(
						'other-members', 'default-guests', 'default-members'
					) NOT NULL DEFAULT 'other-members',
					PRIMARY KEY (`id`),
					KEY `entry_id` (`entry_id`),
					KEY `value` (`value`)
				)
			");
		}
		
		public function isSortable() {
			return true;
		}
		
		public function canFilter() {
			return true;
		}
		
		public function canPrePopulate() {
			return true;
		}
		
		public function allowDatasourceOutputGrouping() {
			return true;
		}
		
		public function allowDatasourceParamOutput() {
			return true;
		}
		
	/*-------------------------------------------------------------------------
		Display functions:
	-------------------------------------------------------------------------*/
		
		public function displayPublishPanel(&$wrapper, $data = null, $flagWithError = null, $fieldnamePrefix = null, $fieldnamePostfix = null) {
			$label = Widget::Label($this->get('label'));
			$name = $this->get('element_name');
			$value = $data['value'];
			$options = array(
				array(
					'other-members', false, 'Other members'
				),
				array(
					'default-guests', false, 'Default guests'
				),
				array(
					'default-members', false, 'Default members'
				)
			);
			
			foreach ($options as $index => $option) {
				$options[$index][1] = $option[0] == $value;
			}
			
			$label->appendChild(Widget::Select(
				"fields{$fieldnamePrefix}[{$name}]{$fieldnamePostfix}", $options
			));
			
			if ($flagWithError != null) {
				$wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
				
			} else {
				$wrapper->appendChild($label);
			}
		}
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper);
			
			$this->appendShowColumnCheckbox($wrapper);
		}
		
	/*-------------------------------------------------------------------------
		Data retrieval functions:
	-------------------------------------------------------------------------*/
		
		public function groupRecords($records) {
			if (!is_array($records) || empty($records)) return;
			
			$groups = array($this->get('element_name') => array());
			
			foreach ($records as $r) {
				$data = $r->getData($this->get('id'));
				
				$value = $data['value'];
				$handle = Lang::createHandle($value);
				
				if (!isset($groups[$this->get('element_name')][$handle])) {
					$groups[$this->get('element_name')][$handle] = array(
						'attr'		=> array(
							'value'		=> $value
						),
						'records'	=> array(),
						'groups'	=> array()
					);
				}
				
				$groups[$this->get('element_name')][$handle]['records'][] = $r;
			}
			
			return $groups;
		}
		
		public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC'){
			$field_id = $this->get('id');
			$joins .= "
				INNER JOIN `tbl_entries_data_{$field_id}` AS ed
				ON (e.id = ed.entry_id)
			";
			$sort = 'ORDER BY ' . (strtolower($order) == 'random' ? 'RAND()' : "ed.value {$order}");
		}
		
		public function prepareTableValue($data, XMLElement $link = null) {
			switch (@$data['value']) {
				case 'default-guests':
					$value = 'Default guests';
					break;
				case 'default-members':
					$value = 'Default members';
					break;
				default:
					$value = 'Other members';
					break;
			}
			
			return parent::prepareTableValue(array('value' => $value), $link);
		}
		
	/*-------------------------------------------------------------------------
		Data processing functions:
	-------------------------------------------------------------------------*/
		
		public function checkPostFieldData($data, &$message, $entry_id = null) {
			$field_id = $this->get('id');
			$entry_id = (integer)$entry_id;
			
			if ($data == 'default-guests' or $data == 'default-members') {
				$taken = (integer)$this->Database->fetchVar('taken', 0, "
					SELECT
						COUNT(f.id) AS `taken`
					FROM
						`tbl_entries_data_{$field_id}` AS f
					WHERE
						f.value = '{$data}'
						AND f.entry_id != {$entry_id}
				");
				
				if ($taken > 0) {
					$message = "This group type can only be used on one group at a time.";
					
					return self::__INVALID_FIELDS__;
				}
			}
			
			return parent::checkPostFieldData($data, $message, $entry_id);
		}
		
		public function processRawFieldData($data, &$status, $simulate = false, $entry_id = null) {
			$status = self::__OK__;
			
			return array(
				'value'		=> General::sanitize($data),
			);
		}
		
		public function commit() {
			if (!parent::commit()) return false;
			
			$id = $this->get('id');
			$handle = $this->handle();
			
			if ($id === false) return false;
			
			$fields = array(
				'field_id'		=> $id
			);
			
			$this->_engine->Database->query("
				DELETE FROM
					`tbl_fields_{$handle}`
				WHERE
					`field_id` = '$id'
				LIMIT 1
			");
			
			return $this->_engine->Database->insert($fields, "tbl_fields_{$handle}");
		}
	}
	
?>