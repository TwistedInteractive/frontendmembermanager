<?php
	
	if (!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');
	
	require_once(TOOLKIT . '/fields/field.input.php');
	
	class FieldGroupName extends FieldInput {
		public function __construct(&$parent) {
			parent::__construct($parent);
			
			$this->_name = 'Group Name';
		}
		
		public function displaySettingsPanel(&$wrapper, $errors = null) {
			$field_id = $this->get('id');
			$oder = $this->get('sortorder');
			
			$wrapper->appendChild(new XMLElement('h4', ucwords($this->name())));
			$wrapper->appendChild(Widget::Input(
				"fields[{$oder}][type]", $this->handle(), 'hidden'
			));
			
			if ($field_id) $wrapper->appendChild(Widget::Input(
				"fields[{$oder}][id]", $field_id, 'hidden'
			));
			
			$wrapper->appendChild($this->buildSummaryBlock($errors));	
			
			$group = new XMLElement('div', null, array('class' => 'group'));
			
			$div = new XMLElement('div');
			
			$this->buildValidationSelect($div, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]');
			
			//$div->appendChild($label);
			
			//$this->appendRequiredCheckbox($div);
			$group->appendChild($div);
			
			$group->appendChild(
				$this->buildFormatterSelect($this->get('formatter'),
				'fields[' . $this->get('sortorder') . '][formatter]', 'Text Formatter')
			);
			
			$wrapper->appendChild($group);
			
			$this->appendRequiredCheckbox($wrapper);
			$this->appendShowColumnCheckbox($wrapper);						
		}
		
		function ddisplaySettingsPanel(&$wrapper, $errors=NULL){
			
			parent::displaySettingsPanel($wrapper, $errors);
			
			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]');		
			
			$this->appendRequiredCheckbox($wrapper);
			$this->appendShowColumnCheckbox($wrapper);
						
		}
	}
	
?>