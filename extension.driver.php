<?php
	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	
	class Extension_FrontendMemberManager extends Extension {
		protected $initialized = false;
		protected $sessions = array();
		
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'Frontend Member Manager',
				'version'		=> '1.0.12',
				'release-date'	=> '2011-03-04',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://rowanlewis.com/',
					'email'			=> 'me@rowanlewis.com'
				),
				'description' => 'Allows you to manage a member driven community.'
			);
		}
		
		public function uninstall() {
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_memberemail`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_memberpassword`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_memberstatus`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fmm_tracking`");
		}
		
		public function install() {
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_memberemail` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`formatter` varchar(255) default NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_memberpassword` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`length` int(11) unsigned NOT NULL,
					`strength` enum('weak', 'good', 'strong') NOT NULL default 'good',
					`salt` varchar(255) default NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_memberstatus` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					PRIMARY KEY (`id`)
				)
			");
			
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fmm_tracking` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`entry_id` int(11) unsigned default NULL,
					`access_id` varchar(32) NOT NULL,
					`client_id` varchar(32) NOT NULL,
					`first_seen` datetime NOT NULL,
					`last_seen` datetime NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `unique_id` (`access_id`,`client_id`),
					KEY `entry_id` (`entry_id`)
				)
			");
		}
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendPageResolved',
					'callback'	=> 'initialize'
				),
				array(
					'page'		=> '/frontend/',
					'delegate'	=> 'FrontendParamsResolve',
					'callback'	=> 'parameters'
				),
				array(
					'page'		=> '/system/preferences/',
					'delegate'	=> 'AddCustomPreferenceFieldsets',
					'callback'	=> 'addCustomPreferenceFieldsets'
				)
			);
		}
		
		protected $addedPublishHeaders = false;
		
		public function addPublishHeaders($page) {
			if (!$this->addedPublishHeaders) {
				$page->addStylesheetToHead(URL . '/extensions/frontendmembermanager/assets/publish.css', 'screen', 8251840);
				
				$this->addedPublishHeaders = true;
			}
		}
		
		public function addSettingsHeaders($page) {
			if (!$this->addedSettingsHeaders) {
				$page->addStylesheetToHead(URL . '/extensions/frontendmembermanager/assets/settings.css', 'screen', 8251840);
				
				$this->addedSettingsHeaders = true;
			}
		}
		
	/*-------------------------------------------------------------------------
		Delegates:
	-------------------------------------------------------------------------*/
		
		public function initialize($context = null) {
			if (!$this->initialized) {
				$sectionManager = new SectionManager($this->_Parent);
				$sections = $this->_Parent->Database->fetchCol('id', "
					SELECT
						s.id
					FROM
						`tbl_sections` AS s
					WHERE
						3 = (
							SELECT
								count(*)
							FROM
								`tbl_fields` AS f
							WHERE
								f.parent_section = s.id
								AND f.type IN (
									'memberemail',
									'memberpassword',
									'memberstatus'
								)
						)
				");
				
				foreach ($sections as $section_id) {
					$this->sessions[] = new FMM_Session(
						$this, $this->_Parent, $this->_Parent->Database,
						$sectionManager->fetch($section_id)
					);
				}
				
				$this->initialized = true;
			}
			
			return true;
		}
		
		public function parameters(&$context) {
			$this->initialize();
			
			foreach ($this->sessions as $session) {
				$session->parameters($context);
			}
		}
		
		public function addCustomPreferenceFieldsets($context) {
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(
				new XMLElement('legend', 'Frontend Member Manager')
			);
			
			$selected_id = $this->_Parent->Configuration->get(
				'recovery-email-template', 'frontendmembermanager'
			);
			
			$driver = $this->_Parent->ExtensionManager->create('emailtemplatefilter');
			$options = array(
				array('', false, __('None'))
			);
			
			foreach ($driver->getTemplates() as $values) {
				$id = $values['id'];
				$name = $values['name'];
				$selected = ($id == $selected_id) ? true : false;
				
				$options[] = array($id, $selected, $name);
			}
			
			$template = Widget::Label('Recovery Email Template');
			$template->appendChild(Widget::Select(
				'settings[frontendmembermanager][recovery-email-template]', $options
			));
			$group->appendChild($template);
			
			$context['wrapper']->appendChild($group);
		}
		
		public function getSession($section) {
			foreach ($this->sessions as $session) if ($section and $session->handle == $section) {
				return $session;			
			}
			
			return null;
		}
		
	/*-------------------------------------------------------------------------
		Actions:
	-------------------------------------------------------------------------*/
		
		public function actionRequestCode($values, $redirect = null) {
			$result = new XMLElement('fmm-request-code');
			$section = @$values['section'];
			
			// Not setup yet:
			if (!$this->initialize()) {
				$result->setAttribute('status', 'not-setup');
				
				return $result;
			}
			
			else {
				$result->setAttribute('status', 'ok');
			}
			
			foreach ($this->sessions as $session) if ($section and $session->handle == $section) {
				
				$parent = new XMLElement('section');
				$parent->setAttribute('handle', $session->handle);
				
				$session->actionRequestCode($parent, $values, $redirect);
				
				$result->appendChild($parent);
			}			
			
			return $result;
		}
		
		public function actionCheckCode($values, $redirect = null) {
			$result = new XMLElement('fmm-check-code');
			$section = @$values['section'];
			
			// Not setup yet:
			if (!$this->initialize()) {
				$result->setAttribute('status', 'not-setup');
				
				return $result;
			}
			
			else {
				$result->setAttribute('status', 'ok');
			}
			
			foreach ($this->sessions as $session) if ($section and $session->handle == $section) {
				$parent = new XMLElement('section');
				$parent->setAttribute('handle', $session->handle);
				
				$session->actionCheckCode($parent, $values, $redirect);
				
				$result->appendChild($parent);			
			}			
			
			return $result;
		}
		
		public function actionLogin($values, $redirect = null) {
			$result = new XMLElement('fmm-login');
			$section = @$values['section'];
			
			// Not setup yet:
			if (!$this->initialize()) {
				$result->setAttribute('status', 'not-setup');
				return $result;
			}
			
			else {
				$result->setAttribute('status', 'ok');
			}
			
			foreach ($this->sessions as $session) if ($section and $session->handle == $section) {
				$parent = new XMLElement('section');
				$parent->setAttribute('handle', $session->handle);
				
				$session->actionLogin($parent, $values, $redirect);
				
				$result->appendChild($parent);
			}			
			
			return $result;
		}
		
		public function actionLogout() {
			$result = new XMLElement('fmm-logout');
			
			foreach ($this->sessions as $session) {
				$parent = new XMLElement('section');
				$parent->setAttribute('handle', $session->handle);
				
				$session->actionLogout($parent);
				
				$result->appendChild($parent);
			}
			
			return $result;
		}
		
		public function actionStatus() {
			$result = new XMLElement('fmm-status');
			
			// Not setup yet:
			if (!$this->initialize()) {
				$result->setAttribute('status', 'not-setup');
				
				return $result;
			}
			
			else {
				$result->setAttribute('status', 'ok');
			}
			
			foreach ($this->sessions as $session) {
				$parent = new XMLElement('section');
				$parent->setAttribute('handle', $session->handle);
				
				$session->actionStatus($parent);
				
				$result->appendChild($parent);
			}
			
			return $result;
		}
	}
	
	class FMM {
		const STATUS_PENDING = 'pending';
		const STATUS_BANNED = 'banned';
		const STATUS_ACTIVE = 'active';
		
		const FIELD_MEMBEREMAIL = 'memberemail';
		const FIELD_MEMBERPASSWORD = 'memberpassword';
		const FIELD_MEMBERSTATUS = 'memberstatus';
		
		const TRACKING_NORMAL = 'normal';
		const TRACKING_LOGIN = 'login';
		const TRACKING_LOGOUT = 'logout';
		
		const RESULT_SUCCESS = 0;
		const RESULT_INCORRECT_PASSWORD = 1;
		const RESULT_INCORRECT_EMAIL = 2;
		const RESULT_INCORRECT_CODE = 3;
		const RESULT_ACCOUNT_BANNED = 4;
		const RESULT_ACCOUNT_PENDING = 5;
        const RESULT_NO_PASSWORD = 6;
        const RESULT_NOT_STRONG_ENOUGH = 7;
		const RESULT_ERROR = 666;
	}
	
	class FMM_Session {
		protected $database = null;
		protected $driver = null;
		protected $parent = null;
		protected $section = null;
		public $handle = null;
		
		public function __construct($driver, $parent, $database, $section) {
			$this->driver = $driver;
			$this->database = $database;
			$this->parent = $parent;
			$this->section = $section;
			$this->handle = $section->get('handle');

			$this->setAccessId(@$_SESSION['fmm'][$this->handle]);
			
			$this->updateTrackingData();
			$this->cleanTrackingData();
		}
		
		public function parameters(&$context) {
			if ($this->getMemberId() and $this->getMemberStatus() == FMM::STATUS_ACTIVE) {
				$context['params']["fmm-{$this->handle}-id"] = $this->getMemberId();
			}
			
			else {
				$context['params']["fmm-{$this->handle}-id"] = 0;
			}
		}
		
	/*-------------------------------------------------------------------------
		Tracking:
	-------------------------------------------------------------------------*/
		
		public function getAccessId() {
			if (empty($this->accessId)) {
				$this->setAccessId(md5($this->handle . time()));
			}
			
			return $this->accessId;
		}
		
		public function setAccessId($access_id) {
			$_SESSION['fmm'][$this->handle] = $this->accessId = $access_id;
			
			return $this;
		}
		
		public function getClientId() {
			if (empty($this->clientId)) {
				$this->clientId = md5($_SERVER['HTTP_USER_AGENT']);
			}
			
			return $this->clientId;
		}
		
		public function cleanTrackingData() {
			$this->database->query("
				DELETE FROM
					`tbl_fmm_tracking`
				WHERE
					`last_seen` < NOW() - INTERVAL 1 MONTH
					AND (
						`access_id` != '{$this->getAccessId()}'
						AND `client_id` != '{$this->getClientId()}'
					)
				LIMIT 10
			");
		}
		
		public function hasTrackingData() {
			return (boolean)$this->database->fetchVar('id', 0, "
				SELECT
					t.id
				FROM
					`tbl_fmm_tracking` AS t
				WHERE
					t.access_id = '{$this->getAccessId()}'
					AND t.client_id = '{$this->getClientId()}'
				LIMIT 1
			");
		}
		
		public function getTrackingData($access_id) {
			return $this->database->fetchRow(0, "
				SELECT
					t.*
				FROM
					`tbl_fmm_tracking` AS t
				WHERE
					t.access_id = '{$this->getAccessId()}'
					AND t.client_id = '{$this->getClientId()}'
				LIMIT 1
			");
		}
		
		public function updateTrackingData($mode = FMM::TRACKING_NORMAL) {
			$current_date = DateTimeObj::get('Y-m-d H:i:s');
			$member_id = $this->getMemberId();
			
			if ($mode == FMM::TRACKING_LOGOUT) $member_id = 0;
			
			Frontend::Page()->_param["fmm-{$this->handle}-id"] = $member_id;
			
			$this->database->query("
				INSERT INTO
					`tbl_fmm_tracking`
				SET
					`entry_id` = '{$member_id}',
					`access_id` = '{$this->getAccessId()}',
					`client_id` = '{$this->getClientId()}',
					`first_seen` = '{$current_date}',
					`last_seen` = '{$current_date}'
				ON DUPLICATE KEY UPDATE
					`entry_id` = '{$member_id}',
					`last_seen` = '{$current_date}'
			");
		}
		
	/*-------------------------------------------------------------------------
		Member:
	-------------------------------------------------------------------------*/
		
		public function getMember() {
			if (is_null($this->member)) {
				$em = new EntryManager($this->parent);
				
				$this->member = current($em->fetch(
					$this->getMemberId(), $this->section->get('id')
				));
			}
			
			return $this->member;
		}
		
		public function setMember($entry) {
			if ($entry instanceof Entry) {
				$this->member = $entry;
				$this->memberId = $entry->get('id');
			}
			
			return $this;
		}
		
		public function getMemberId() {
			if (empty($this->memberId)) {
				$member_id = $this->database->fetchVar('entry_id', 0, "
					SELECT
						t.entry_id
					FROM
						`tbl_fmm_tracking` AS t
					WHERE
						t.access_id = '{$this->getAccessId()}'
						AND t.client_id = '{$this->getClientId()}'
					LIMIT 1
				");
				
				$this->setMemberId($member_id);
			}
			
			return $this->memberId;
		}
		
		public function setMemberId($entry_id) {
			$this->memberId = (integer)$entry_id;
			
			return $this;
		}
		
		public function getMemberStatus() {
			if (
				$entry = $this->getMember()
				and $field = $this->getMemberField(FMM::FIELD_MEMBERSTATUS)
			) {
				$field = $this->getMemberField(FMM::FIELD_MEMBERSTATUS);
				$data = $entry->getData($field->get('id'));
				$data = $field->sanitizeData($data);
				
				return $data['value'];
			}
			
			return null;
		}
		
		public function setMemberStatus($status) {
			if (
				$entry = $this->getMember()
				and $field = $this->getMemberField(FMM::FIELD_MEMBERSTATUS)
			) {
				$return = null;
				
				// Get updated entry data:
				$data = $field->processRawFieldData(
					$status, $return, false, $entry->get('id')
				);
				
				$entry->setData($field->get('id'), $data);
				
				// Save to database:
				$entry->commit();
			}
			
			return $this;
		}
		
		public function getMemberField($type) {
			return current($this->section->fetchFields($type));
		}
		
	/*-------------------------------------------------------------------------
		Actions:
	-------------------------------------------------------------------------*/
		
		public function actionRequestCode($result, $values, $redirect) {
			$em = new EntryManager($this->parent);
			$fm = new FieldManager($this->parent);
			
			$section = $this->section;
			$where = $joins = $group = null;
			$name_where = $name_joins = $name_group = null;
			
			// Get given fields:
			foreach ($values as $key => $value) {
				$field_id = $fm->fetchFieldIDFromElementName($key, $this->section->get('id'));
				
				if (!is_null($field_id)) {
					$field = $fm->fetch($field_id, $this->section->get('id'));
					
					if ($field instanceof FieldMemberEmail) {
						$field->buildDSRetrivalSQL($value, $joins, $where);
					}
				}
			}
			
			// Find matching entries:
			$entries = $em->fetch(
				null, $this->section->get('id'), 1, null,
				$where, $joins, $group, true
			);
			
			if (!$entry = @current($entries)) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'incorrect-email');
				
				return FMM::RESULT_INCORRECT_EMAIL;
			}
			
			$field = $this->getMemberField(FMM::FIELD_MEMBERSTATUS);
			$data = $entry->getData($field->get('id'));
            $status = is_array($data['value']) ? current($data['value']) : $data['value'];
			
			// The member is banned:
			if ($status == FMM::STATUS_BANNED) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'banned');
				
				return FMM::RESULT_ACCOUNT_BANNED;
			}
			
			// The member is inactive:
			if ($status == FMM::STATUS_PENDING) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'pending');
				
				return FMM::RESULT_ACCOUNT_PENDING;
			}
			
			$email_field = $this->getMemberField(FMM::FIELD_MEMBEREMAIL);
			$email_data = $entry->getData($email_field->get('id'));
			$password_field = $this->getMemberField(FMM::FIELD_MEMBERPASSWORD);
			$password_data = $entry->getData($password_field->get('id'));
			
			// Save new recovery code:
			$password_data['recovery_code'] = md5(
				time() . $entry->get('id') . $email_data['value']
			);
			
			$entry->setData($password_field->get('id'), $password_data);
			$entry->commit();
			
			// Send recovery email:
			$driver = Frontend::Page()->ExtensionManager->create('emailtemplatefilter');
			$template_id = $this->parent->Configuration->get(
				'recovery-email-template', 'frontendmembermanager'
			);
			
			$driver->sendEmail($entry->get('id'), $template_id);
			
			$result->setAttribute('status', 'success');
			
			if (!is_null($redirect)) redirect($redirect);
			
			return FMM::RESULT_SUCCESS;
		}
		
		public function actionCheckCode($result, $values, $redirect) {
			$em = new EntryManager($this->parent);
			$fm = new FieldManager($this->parent);
			
			$section = $this->section;
			$where = $joins = $group = null;
			$name_where = $name_joins = $name_group = null;
			
			// Get given fields:
			foreach ($values as $key => $value) {
				$field_id = $fm->fetchFieldIDFromElementName($key, $this->section->get('id'));
				
				if (!is_null($field_id)) {
					$field = $fm->fetch($field_id, $this->section->get('id'));
					
					if ($field instanceof FieldMemberEmail) {
						$field->buildDSRetrivalSQL($value, $joins, $where);
					}
				}
			}
			
			// Find matching entries:
			$entries = $em->fetch(
				null, $this->section->get('id'), 1, null,
				$where, $joins, $group, true
			);
			
			if (!$entry = @current($entries)) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('status', 'incorrect-email');
				
				return FMM::RESULT_INCORRECT_EMAIL;
			}
			
			$field = $this->getMemberField(FMM::FIELD_MEMBERSTATUS);
			$data = $entry->getData($field->get('id'));
			$status = @current($data['value']);
			
			// The member is banned:
			if ($status == FMM::STATUS_BANNED) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'banned');
				
				return FMM::RESULT_ACCOUNT_BANNED;
			}
			
			// The member is inactive:
			if ($status == FMM::STATUS_PENDING) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'pending');
				
				return FMM::RESULT_ACCOUNT_PENDING;
			}
			
			if (!isset($values['recovery-code']) or $values['recovery-code'] == '') {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'missing-code');
				
				return FMM::RESULT_INCORRECT_CODE;
			}
			
			$password_field = $this->getMemberField(FMM::FIELD_MEMBERPASSWORD);
			$password_data = $entry->getData($password_field->get('id'));
			
			if ($password_data['recovery_code'] != $values['recovery-code']) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'incorrect-code');
				
				return FMM::RESULT_INCORRECT_CODE;
			}
			
			// Reset password:
			if(isset($values['password']) && isset($values['confirm'])) {
				if ($values['password'] == $values['confirm']) {
					$password_data['password'] = $password_field->encodePassword($values['password']);
				}
				
				else {
					$result->setAttribute('status', 'failed');
					$result->setAttribute('reason', 'passwords-mismatch');
					
					return FMM::RESULT_INCORRECT_PASSWORD;
				}
			} else {
                // No password data sent
                $result->setAttribute('status', 'failed');
                $result->setAttribute('reason', 'no-password-sent');
                return FMM::RESULT_NO_PASSWORD;
            }

            // Check password strength:
            $strength = $password_field->checkPassword($values['password']);
            if(!$password_field->compareStrength($strength, $password_field->get('strength')))
            {
                // Not strong enough!
                $result->setAttribute('status', 'failed');
                $result->setAttribute('reason', 'not-strong-enough');

                return FMM::RESULT_NOT_STRONG_ENOUGH;
            }

			// Delete recovery code:
			$password_data['recovery_code'] = null;

			$entry->setData($password_field->get('id'), $password_data);
			$entry->commit();
			
			$result->setAttribute('status', 'success');
			
			$this->setMember($entry);
			$this->updateTrackingData(FMM::TRACKING_LOGIN);
			
			if (!is_null($redirect)) redirect($redirect);
			
			return FMM::RESULT_SUCCESS;
		}
		
		public function actionLogin($result, $values, $redirect, $simulate = false) {
			$em = new EntryManager($this->parent);
			$fm = new FieldManager($this->parent);
			
			$section = $this->section;
			$where = $joins = $group = null;
			$name_where = $name_joins = $name_group = null;
			
			$has_email = false;
			$has_password = false;
			
			// Get given fields:
			foreach ($values as $key => $value) {
				
				$field_id = $fm->fetchFieldIDFromElementName($key, $this->section->get('id'));
				
				if (!is_null($field_id)) {
					$field = $fm->fetch($field_id, $this->section->get('id'));
					
					if ($field instanceof FieldMemberEmail) $has_email = true;
					if ($field instanceof FieldMemberPassword) $has_password = true;
					
					if (
						$field instanceof FieldMemberEmail
						or $field instanceof FieldMemberPassword
					) {
						$field->buildDSRetrivalSQL($value, $joins, $where);
						
						if (!$group) $group = $field->requiresSQLGrouping();
						
						// Build SQL for determining of the username or the password was
						// incorrrect. Only executed if login fails
						if ($field instanceof FieldMemberEmail) {
							$field->buildDSRetrivalSQL($value, $name_joins, $name_where);
							if (!$name_group) $name_group = $field->requiresSQLGrouping();
						}
					}
				}
			}
			
			if ($has_email == false || $has_password == false) {
				$result->setAttribute('status', 'failed');
				if ($has_email == false) $result->setAttribute('reason', 'missing-email-field');
				if ($has_password == false) $result->setAttribute('reason', 'missing-password-field');
				return FMM::RESULT_ERROR;
			}
			
			// Find matching entries:
			$entries = $em->fetch(
				null, $this->section->get('id'), 1, null,
				$where, $joins, $group, true
			);
			
			// Invalid credentials, woot!
			if (!$entry = @current($entries)) {
				$result->setAttribute('status', 'failed');
				
				// Determine reason for login failure.
				$name_entries = $em->fetch(
					null, $this->section->get('id'), 1, null,
					$name_where, $name_joins, $name_group, true
				);
				
				if ($name_entry = @current($name_entries)) {
					$result->setAttribute('reason', 'incorrect-password');
					
					return FMM::RESULT_INCORRECT_PASSWORD;
				}
				
				else {
					$result->setAttribute('reason', 'incorrect-email');
					
					return FMM::RESULT_INCORRECT_EMAIL;
				}
			}
			
			$this->setMember($entry);
			$field = $this->getMemberField(FMM::FIELD_MEMBERSTATUS);
			$data = $entry->getData($field->get('id'));
			$status = is_array($data['value']) ? current($data['value']) : $data['value'];
			
			// The member is banned:
			if ($status == FMM::STATUS_BANNED) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'banned');
				
				return FMM::RESULT_ACCOUNT_BANNED;
			}
			
			// The member is inactive:
			if ($status == FMM::STATUS_PENDING) {
				$result->setAttribute('status', 'failed');
				$result->setAttribute('reason', 'pending');
				
				return FMM::RESULT_ACCOUNT_PENDING;
			}
			
			$result->setAttribute('status', 'success');
			
			if (!$simulate) {
				$this->updateTrackingData(FMM::TRACKING_LOGIN);
				
				if (!is_null($redirect)) redirect($redirect);
			}
			
			return FMM::RESULT_SUCCESS;
		}
		
		public function actionLogout($result) {
			$this->updateTrackingData(FMM::TRACKING_LOGOUT);
			
			$result->setAttribute('status', 'success');
			
			return FMM::RESULT_SUCCESS;
		}
		
		public function actionStatus($result) {
			if ($this->getMemberId() and $this->getMemberStatus() == FMM::STATUS_ACTIVE) {
				$result->setAttribute('logged-in', 'yes');
			}
			
			else {
				$result->setAttribute('logged-in', 'no');
			}
			
			return FMM::RESULT_SUCCESS;
		}
	};
	
?>
