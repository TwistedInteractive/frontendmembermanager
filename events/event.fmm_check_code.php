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
	New password: <input type="text" name="fields[my-password-field][password]" /><br />
	Confirm: <input type="text" name="fields[my-password-field][confirm]" /><br />
	<input type="hidden" name="fields[recovery-code]" value="{$code}" />
	<input type="hidden" name="fields[section]" value="users" />
	<input type="submit" name="action[check-code]" value="Login" />
</form>').'</code></pre>
			<h3>Resetting passwords</h3>
			<p>You can also use this feature to allow the user to reset their password. What you need to do is create an event to modify the users-section. Secondly, you need to create a datasource which retreives the user-id according to the `$code`-parameter</p>
			<p>An example of this form could look something like this:</p>
			<pre class="xml"><code>'.htmlentities('<form method="post" action="">
	New password: <input type="text" name="fields[my-password-field][password]" /><br />
	Confirm: <input type="text" name="fields[my-password-field][confirm]" /><br />
	<input type="hidden" name="fields[recovery-code]" value="{$code}" />
	<input type="hidden" name="fields[section]" value="users" />
	<input type="hidden" name="id" value="{data/users-id/entry/@id}" />
	<input type="hidden" name="action[change-password]" value="1" />
	<input type="submit" name="action[check-code]" value="Reset password" />
</form>').'</code></pre>
			<p><strong>Important security note:</strong></p>
			<p>When resetting the passwords, make sure you edit the event so it doesn\'t allow adding new entries or editing other entries other than the one with the current recovery code. You can do this by modifying your event with the following code:</p>
			<pre class="xml"><code>'.htmlentities('// Change this rule:
// if(isset($_POST[\'action\'][\'your-event-name\'])) return $this->__trigger();
// To this one:
if(isset($_POST[\'action\'][\'change-password\']) && isset($_POST[\'id\']) && isset($_POST[\'fields\'][\'recovery-code\']) && isset($_POST[\'fields\'][\'section\']))
{
	include_once(TOOLKIT.\'/class.sectionmanager.php\');
	$sm      = new SectionManager($this);
	$section = $_POST[\'fields\'][\'section\'];
	$code    = $_POST[\'fields\'][\'recovery-code\'];
	$idEntry = $_POST[\'id\'];
	$sectionID = $sm->fetchIDFromHandle($section);
	$result = Symphony::Database()->fetch("SELECT `id` FROM `tbl_fields` WHERE `type` = \'memberpassword\' AND `parent_section` = ".$sectionID.";");
	$fieldID = $result[0][\'id\'];
	$result = Symphony::Database()->fetch("SELECT COUNT(*) AS `total` FROM `tbl_entries_data_".$fieldID."` WHERE `recovery_code` = \'".$code."\' AND `entry_id` = ".$idEntry.";");
	$total = $result[0][\'total\'];
	if($total == 1)
	{
		return $this->__trigger();
	}
}').'</code></pre>
			<p>Also, set the return value of the function `allowEditorToParse()` to `false`.</p>
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