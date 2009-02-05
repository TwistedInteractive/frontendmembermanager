<?php
	
	class EventFMM_Login extends Event {
		protected $results = null;
		
		public static function about() {
			return array(
				'name'				=> 'Frontend Member Manager: Login',
				'author'			=> array(
					'name'				=> 'Rowan Lewis',
					'website'			=> 'http://www.pixelcarnage.com/',
					'email'				=> 'rowan@pixelcarnage.com'
				),
				'version'			=> '1.0',
				'release-date'		=> '2009-02-03',
				'trigger-condition'	=> 'action[login] field or an already valid Symphony cookie.'
			);
		}
		
		public function load() {
			if (isset($_REQUEST['action']['login'])) return $this->__trigger();
		}
		
		protected function __trigger() {
			$this->result = new XMLElement('fmm-login');
			$driver = $this->_Parent->ExtensionManager->create('frontendmembermanager');
			$em = new EntryManager($this->_Parent);
			$fm = new FieldManager($this->_Parent);
			
			// Not setup yet:
			if (!$driver->initialize()) {
				$this->setStatus('not-setup');
				return $this->result;
			}
			
			$values = @$_REQUEST['fields']; $fields = array();
			$section = $driver->getSection();
			$where = $joins = $group = null;
			
			// Get given fields:
			foreach ($values as $key => $value) {
				$field_id = $fm->fetchFieldIDFromElementName($key, $section->get('id'));
				
				if (!is_null($field_id)) {
					$fields[] = $field = $fm->fetch($field_id, $section->get('id'));
					
					$field->buildDSRetrivalSQL($value, $joins, $where);
					
					if (!$group) $group = $field->requiresSQLGrouping();
				}
			}
			
			// Find matching entries:
			$entries = $em->fetch(
				null, $section->get('id'), 1, null,
				$where, $joins, $group, true
			);
			
			// Invalid credentials, woot!
			if (!$entry = @current($entries)) {
				$this->setStatus('failed');
				return $this->result;
			}
			
			$driver->setMember($entry);
			$field = $driver->getMemberField(FMM::FIELD_MEMBERSTATUS);
			$data = $entry->getData($field->get('id'));
			$status = @current($data['value']);
			
			// The member is banned:
			if ($status == FMM::STATUS_BANNED) {
				$this->setStatus('banned');
				return $this->result;
			}
			
			// The member is inactive:
			if ($status == FMM::STATUS_PENDING) {
				$this->setStatus('pending');
				return $this->result;
			}
			
			$this->setStatus('success');
			$driver->updateTrackingData(FMM::TRACKING_LOGIN);
			
			return $this->result;
		}
		
		protected function setStatus($message) {
			$this->result->setAttribute('status', $message);
		}
	}
	
?>