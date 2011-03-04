<?php
	
	class EventFMM_Login extends Event {
		protected $results = null;
		
		public static function about() {
			return array(
				'name'				=> 'Frontend Member Manager: Login',
				'author'			=> array(
					'name'				=> 'Rowan Lewis',
					'website'			=> 'http://rowanlewis.com/',
					'email'				=> 'me@rowanlewis.com'
				),
				'version'			=> '1.0',
				'release-date'		=> '2009-02-03',
				'trigger-condition'	=> 'action[login] field or an already valid Symphony cookie.'
			);
		}
		
		public static function documentation() {
			return file_get_contents(dirname(__FILE__) . '/' . basename(__FILE__, '.php') . '.html');
		}
		
		public function load() {
			if (isset($_REQUEST['action']['login'])) return $this->__trigger();
		}
		
		protected function __trigger() {
			$driver = $this->_Parent->ExtensionManager->create('frontendmembermanager');
			return $driver->actionLogin($_REQUEST['fields'],@$_REQUEST['redirect']);
		}
	}
	
?>