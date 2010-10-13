<?php
	
	class EventFMM_Logout extends Event {
		public static function about() {
			return array(
				'name'				=> 'Frontend Member Manager: Logout',
				'author'			=> array(
					'name'				=> 'Rowan Lewis',
					'website'			=> 'http://www.pixelcarnage.com/',
					'email'				=> 'rowan@pixelcarnage.com'
				),
				'version'			=> '1.0',
				'release-date'		=> '2009-02-05'
			);
		}
		
		public function load() {
			return $this->__trigger();
		}
		
		public function documentation()
		{
			return '<p>Add this event to the logout page. As soon as the user enters this page a logout is performed.</p>';
		}
		
		protected function __trigger() {
			$driver = Frontend::Page()->ExtensionManager->create('frontendmembermanager');
			return $driver->actionLogout();
		}
	}
	
?>