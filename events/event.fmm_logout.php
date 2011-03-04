<?php
	
	class EventFMM_Logout extends Event {
		public static function about() {
			return array(
				'name'				=> 'Frontend Member Manager: Logout',
				'author'			=> array(
					'name'				=> 'Rowan Lewis',
					'website'			=> 'http://rowanlewis.com/',
					'email'				=> 'me@rowanlewis.com'
				),
				'version'			=> '1.0',
				'release-date'		=> '2009-02-05'
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
			return $driver->actionLogout();
		}
	}
	
?>