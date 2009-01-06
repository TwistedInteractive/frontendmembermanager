<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	class FieldGroupLink extends Field {
		protected $_driver = null;
		
	/*-------------------------------------------------------------------------
		Field definition:
	-------------------------------------------------------------------------*/
		
		public function __construct(&$parent) {
			parent::__construct($parent);
			
			$this->_name = 'Group Link';
			$this->_driver = $this->_engine->ExtensionManager->create('membermanager');
			
			// Set defaults:
			$this->set('show_column', 'yes');
		}
		
		public function createTable() {
			$field_id = $this->get('id');
			
			return $this->_engine->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_entries_data_{$field_id}` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned NOT NULL,
					`value` int(11) unsigned NOT NULL,
					PRIMARY KEY  (`id`),
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
			$options = array();
			$section_id = $this->get('section_id');
			
			// Find possible values:
			$name_link_id = $this->Database->fetchVar('id', 0, "
				SELECT
					f.id
				FROM
					`tbl_fields` f
				WHERE
					f.type = 'groupname'
					AND f.parent_section = {$section_id}
				LIMIT 1
			");
			$values = $this->Database->fetch("
				SELECT
					f.value,
					f.entry_id
				FROM
					`tbl_entries_data_{$name_link_id}` f
				ORDER BY
					f.value ASC
			");
			
			// Find default value:
			$type_link_id = $this->Database->fetchVar('id', 0, "
				SELECT
					f.id
				FROM
					`tbl_fields` f
				WHERE
					f.type = 'grouptype'
					AND f.parent_section = {$section_id}
				LIMIT 1
			");
			$default_id = (integer)$this->Database->fetchVar('entry_id', 0, "
				SELECT
					f.entry_id
				FROM
					`tbl_entries_data_{$type_link_id}` f
				WHERE
					f.value = 'default-members'
				LIMIT 1
			");
			
			$value = (integer)((integer)$data['value'] > 0 ? $data['value'] : $default_id);
			
			if (is_array($values) and !empty($values)) {
				foreach ($values as $row) {
					$options[] = array(
						$row['entry_id'], $row['entry_id'] == $value, $row['value']
					);
				}
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
			
			$order = $this->get('sortorder');
			$section_id = $this->get('section_id');
			$section = (integer)$this->get('parent_section');
			$sections = $this->Database->fetch("
				SELECT DISTINCT
					s.id, s.name 
				FROM
					`tbl_sections` AS s
				WHERE
					s.id != {$section}
					AND s.id IN (
						SELECT
							f.parent_section
						FROM
							`tbl_fields` AS f
						WHERE
							f.type = 'groupname'
					)
				ORDER BY s.sortorder
			");
			
			$label = Widget::Label('Groups Section');
			$options = array();
			
			if (is_array($sections) and !empty($sections)) {
				foreach ($sections as $section) {
					$options[] = array(
						$section['id'], $section['id'] == $section_id, $section['name']
					);
				}
			}
			
			$label->appendChild(Widget::Select(
				"fields[{$order}][section_id]", $options
			));
			
			$wrapper->appendChild($label);
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
			$section_id = $this->get('section_id');
			$entry_id = @(integer)$data['value'];
			
			$link_id = $this->Database->fetchVar('id', 0, "
				SELECT
					f.id
				FROM
					`tbl_fields` f
				WHERE
					f.type = 'groupname'
					AND f.parent_section = {$section_id}
			");
			$value = $this->Database->fetchVar('value', 0, "
				SELECT
					f.value
				FROM
					`tbl_entries_data_{$link_id}` f
				WHERE
					f.entry_id = {$entry_id}
				LIMIT 1
			");
			
			return parent::prepareTableValue(array('value' => $value), $link);
		}
		
	/*-------------------------------------------------------------------------
		Data processing functions:
	-------------------------------------------------------------------------*/
		
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
				'field_id'		=> $id,
				'section_id'	=> $this->get('section_id')
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