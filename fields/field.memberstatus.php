<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	class FieldMemberStatus extends Field {
		protected $_driver = null;
		protected $_states = array();
		
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function __construct(&$parent) {
			parent::__construct($parent);
			
			$this->_name = 'Member Status';
			$this->_driver = $this->_engine->ExtensionManager->create('frontendmembermanager');
			$this->_states = array(
				//'pending'	=> array('pending', false, 'Pending'),
				'banned'	=> array('banned', false, 'Banned'),
				'offline'	=> array('offline', true, 'Offline'),
				'online'	=> array('online', false, 'Online')
			);
			
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
						'pending', 'banned', 'offline', 'online'
					) NOT NULL DEFAULT 'pending',
					`date` datetime default NULL,
					PRIMARY KEY (`id`),
					KEY `entry_id` (`entry_id`),
					KEY `value` (`value`),
					KEY `date` (`date`)
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
		Utilities:
	-------------------------------------------------------------------------*/
		
		public function isBanned($rows) {
			if (is_array($rows)) {
				if (is_array($rows['value'])) {
					$data = array(
						'value'		=> current($rows['value']),
						'date'		=> current($rows['date'])
					);
					
				} else {
					$data = $rows;
				}
				
			} else {
				$data = array();
			}
			
			return @$data['value'] == 'banned';
		}
		
	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper);
			
			$this->appendShowColumnCheckbox($wrapper);
		}
		
		public function commit() {
			$id = $this->get('id');
			$handle = $this->handle();
			
			if (!parent::commit() or $id === false) return false;
			
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
		
	/*-------------------------------------------------------------------------
		Publish:
	-------------------------------------------------------------------------*/
		
		public function displayPublishPanel(&$wrapper, $rows = null, $error = null, $prefix = null, $postfix = null) {
			if (is_array($rows)) {
				if (is_array($rows['value'])) {
					$data = array(
						'value'		=> current($rows['value']),
						'date'		=> current($rows['date'])
					);
					
				} else {
					$data = $rows;
				}
				
			} else {
				$data = array();
			}
			
			$label = Widget::Label($this->get('label'));
			$name = $this->get('element_name');
			$value = $data['value'];
			$options = $this->_states;
			
			if (!is_null($value)) foreach ($options as $index => $option) {
				$options[$index][1] = $option[0] == $value;
			}
			
			$label->appendChild(Widget::Select(
				"fields{$prefix}[{$name}]{$postfix}", $options
			));
			
			if ($error != null) {
				$label = Widget::wrapFormElementWithError($label, $error);
			}
			
			$wrapper->appendChild($label);
		}
		
	/*-------------------------------------------------------------------------
		Input:
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
			$field_id = $this->get('id');
			$entry_id = (integer)$entry_id;
			
			$current = $this->Database->fetch("
				SELECT
					f.value, f.date
				FROM
					`tbl_entries_data_{$field_id}` AS f
				WHERE
					f.entry_id = '{$entry_id}'
				ORDER BY
					f.date DESC
				LIMIT 4
			");
			
			$values = array(
				'value'		=> array($data),
				'date'		=> array(DateTimeObj::get('Y-m-d H:i:s'))
			);
			
			foreach ($current as $item) {
				$values['value'][] = $item['value'];
				$values['date'][] = $item['date'];
			}
			
			return $values;
		}
		
	/*-------------------------------------------------------------------------
		Output:
	-------------------------------------------------------------------------*/
		
		public function prepareTableValue($data, XMLElement $link = null) {
			return parent::prepareTableValue(array(
				'value'	=> @$this->_states[$data['value']][2]
			), $link);
		}
		
	/*-------------------------------------------------------------------------
		Sorting:
	-------------------------------------------------------------------------*/
		
		public function buildSortingSQL(&$joins, &$where, &$sort, $order = 'ASC') {
			$field_id = $this->get('id');
			$joins .= "
				INNER JOIN `tbl_entries_data_{$field_id}` AS ed
				ON (e.id = ed.entry_id)
			";
			$sort = 'ORDER BY ' . (strtolower($order) == 'random' ? 'RAND()' : "ed.value {$order}");
		}
		
	/*-------------------------------------------------------------------------
		Grouping:
	-------------------------------------------------------------------------*/
		
		public function groupRecords($records) {
			if (!is_array($records) or empty($records)) return;
			
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
	}
	
?>