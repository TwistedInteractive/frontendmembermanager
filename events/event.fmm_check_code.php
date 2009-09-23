<?php
	
	class EventFMM_Check_Code extends Event {
		protected $results = null;
		
		public static function about() {
			return array(
				'name'				=> 'Frontend Member Manager: Check Code',
				'author'			=> array(
					'name'				=> 'Rowan Lewis',
					'website'			=> 'http://rowanlewis.com/',
					'email'				=> 'me@rowanlewis.com'
				),
				'version'			=> '1.0.1',
				'release-date'		=> '2009-09-22',
				'trigger-condition'	=> 'action[check-code]'
			);
		}
		
		public function load() {
			if (isset($_REQUEST['action']['check-code'])) return $this->__trigger();
		}
		
		protected function __trigger() {
			$driver = $this->_Parent->ExtensionManager->create('frontendmembermanager');
			
			return $driver->actionCheckCode(@$_REQUEST['fields'], @$_REQUEST['redirect']);
		}
	}
	
?>