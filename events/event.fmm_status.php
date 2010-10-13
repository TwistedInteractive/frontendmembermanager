<?php
	
	class EventFMM_Status extends Event {
		public static function about() {
			return array(
				'name'				=> 'Frontend Member Manager: Status',
				'author'			=> array(
					'name'				=> 'Rowan Lewis',
					'website'			=> 'http://www.pixelcarnage.com/',
					'email'				=> 'rowan@pixelcarnage.com'
				),
				'version'			=> '1.0',
				'release-date'		=> '2009-02-03'
			);
		}
		
		public function documentation()
		{
			return '<p>Provides information about the login-status of the user.</p>
<p>Example output:</p>
<pre class="xml"><code>'.htmlentities('<fmm-status status="ok">
	<section handle="users" logged-in="yes" />
</fmm-status>
').'</pre></code>
';
		
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