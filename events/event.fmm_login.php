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
		
		public function documentation()
		{
			return '<p>To login, you have to create a form with the following fields:</p>
		<ul>
			<li>An input field for the e-mail address with as name the handle of your e-mail field.</li>
			<li>An input field for the password with with as name the handle of your password field.</li>
			<li>A hidden field with the section name of the users-section.</li>
			<li>A submit-button with the name <code>action[login]</code>.</li>
			<li><em>(optional):</em> A hidden field with the URL to redirect to after login.</li>
		</ul>
			<p>An example login form:</p>
<pre class="xml"><code>'.htmlentities('<form method="post" action="">
	E-mail address: <input type="text" name="fields[e-mail-address]" />
	Password: <input type="text" name="fields[password]" />
	<input type="hidden" name="fields[section]" value="users" />
	<input type="hidden" name="redirect" value="/" />
	<input type="submit" name="action[login]" value="Login" />
</form>').'
</code></pre>
<p>When a user is logged in, there will be a extra parameter available called <code>$fmm-<em>(section name)</em>-id</code> with the ID of the currently logged in user. You can use this parameter for example to retrieve user information from your users section.</p>
';
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