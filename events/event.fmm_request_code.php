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
		
		public function documentation()
		{
			return '<p>This event is used to make use of the forgot password feature?</p>
			<p>To set it up, make sure you got the <a href="http://symphony-cms.com/download/extensions/view/20743/">E-mail template filter</a> installed, and follow the following steps:</p>
			<ul>
				<li>Read the tutorial accomplied with the E-mail Template Filter.</li>
				<li>Create an e-mail template for password retreival and set it in the preferences page.</li>
			</ul>
			<p>An example form could look something like this:</p>
			<pre class="xml"><code>'.htmlentities('<form method="post" action="">
	E-mail address: <input type="text" name="fields[e-mail-address]" />
	<input type="hidden" name="fields[section]" value="users" />
	<input type="submit" name="action[request-code]" value="Reset password" />
</form>').'</code></pre>
			';
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