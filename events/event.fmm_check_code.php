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
		
		public function documentation()
		{
			return '<p>Put this event on the page where you let your user login according to the code supplied to them. The `$code` is provided to the page as a parameter (for example: the user clicked on a generated link in the e-mail he received when he claimed he lost his password).</p>
			<p>An example form could look something like this:</p>
			<pre class="xml"><code>'.htmlentities('<form method="post" action="">
	<input type="hidden" name="fields[recovery-code]" value="{$code}" />
	<input type="hidden" name="fields[section]" value="users" />
	<input type="submit" name="action[check-code]" value="Login" />
</form>').'</code></pre>
			<h3>Resetting passwords</h3>
			<p>You can also use this feature to allow the user to reset their password. The only thing you need to do for that is supply a `password`-, an `email`- and a `confirm`-field. The e-mail address field should have the same name as the field of your users-section.</p>
			<p>An example of this form could look something like this:</p>
			<pre class="xml"><code>'.htmlentities('<form method="post" action="">
	E-mail addres: <input type="text" name="fields[e-mail-address]" /><br />
	New password: <input type="text" name="fields[password]" /><br />
	Confirm: <input type="text" name="fields[confirm]" /><br />
	<input type="hidden" name="fields[recovery-code]" value="{$code}" />
	<input type="hidden" name="fields[section]" value="users" />
	<input type="submit" name="action[check-code]" value="Change password" />
</form>').'</code></pre>			
			';
		}
		
		public function load() {
			if (isset($_REQUEST['action']['check-code'])) return $this->__trigger();
		}
		
		protected function __trigger() {
			$driver = Frontend::Page()->ExtensionManager->create('frontendmembermanager');
			return $driver->actionCheckCode(@$_REQUEST['fields'], @$_REQUEST['redirect']);
		}
	}
	
?>