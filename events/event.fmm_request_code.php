<?php
	
	class EventFMM_Request_Code extends Event {
		protected $results = null;
		
		public static function about() {
			return array(
				'name'				=> 'Frontend Member Manager: Request Code',
				'author'			=> array(
					'name'				=> 'Rowan Lewis',
					'website'			=> 'http://rowanlewis.com/',
					'email'				=> 'me@rowanlewis.com'
				),
				'version'			=> '1.0.1',
				'release-date'		=> '2009-09-22',
				'trigger-condition'	=> 'action[request-code]'
			);
		}
		
		public static function documentation() {
			return file_get_contents(dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '.html');
		}
		
		public function load() {
			if (isset($_REQUEST['action']['request-code'])) return $this->__trigger();
		}
		
		protected function __trigger() {
			$driver = Frontend::Page()->ExtensionManager->create('frontendmembermanager');
			
			return $driver->actionRequestCode(@$_REQUEST['fields'], @$_REQUEST['redirect']);
		}
	}
	
?>