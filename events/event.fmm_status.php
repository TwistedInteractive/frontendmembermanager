<?php
	
	class EventFMM_Status extends Event {
		public static function about() {
			return array(
				'name'				=> 'Frontend Member Manager: Status',
				'author'			=> array(
					'name'				=> 'Rowan Lewis',
					'website'			=> 'http://rowanlewis.com/',
					'email'				=> 'me@rowanlewis.com'
				),
				'version'			=> '1.0',
				'release-date'		=> '2009-02-03'
			);
		}
		
		public static function documentation() {
			return file_get_contents(dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '.html');
		}
		
		public function load() {
			return $this->__trigger();
		}
		
		protected function __trigger() {
			$driver = Frontend::Page()->ExtensionManager->create('frontendmembermanager');
			return $driver->actionStatus();
		}
	}
	
?>