<?php
	
	require_once(TOOLKIT . '/class.sectionmanager.php');
	require_once(TOOLKIT . '/class.fieldmanager.php');
	require_once(TOOLKIT . '/class.entrymanager.php');
	
	class Extension_FrontendMemberManager extends Extension {
		protected $sectionManager = null;
		protected $entryManager = null;
		protected $section = null;
		protected $sectionId = 0;
		protected $member = null;
		protected $memberId = 0;
		protected $accessId = null;
		protected $clientId = null;
		protected $initialized = false;
		
	/*-------------------------------------------------------------------------
		Definition:
	-------------------------------------------------------------------------*/
		
		public function about() {
			return array(
				'name'			=> 'Frontend Member Manager',
				'version'		=> '1.005',
				'release-date'	=> '2009-02-04',
				'author'		=> array(
					'name'			=> 'Rowan Lewis',
					'website'		=> 'http://pixelcarnage.com/',
					'email'			=> 'rowan@pixelcarnage.com'
				),
				'description' => 'Allows you to manage a member driven community.'
			);
		}
		
		public function uninstall() {
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_groupname`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_grouptype`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_membergroup`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_membername`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_memberpassword`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_memberstatus`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fmm_tracking`");
		}
		
		public function install() {
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_groupname` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`formatter` varchar(255) default NULL,
					`validator` varchar(255) default NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_grouptype` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
			
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_membergroup` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`parent_section_id` int(11) unsigned default NULL,
					`parent_field_id` int(11) unsigned default NULL,
					PRIMARY KEY (`id`),
					KEY `parent_section_id` (`parent_section_id`),
					KEY `parent_field_id` (`parent_field_id`)
				)
			");
			
			$this->_Parent->Database->query("
				CREATE TABLE IF NOT EXISTS `tbl_fields_membername` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`formatter` varchar(255) default NULL,
					`validator` varchar(255) default NULL,
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
				)
			);
		}
		
	/*-------------------------------------------------------------------------
		Delegates:
	-------------------------------------------------------------------------*/
		
		public function initialize($context) {
			if ($this->initialized) return; $this->initialized = true;
			
			$this->sectionManager = new SectionManager($this->_Parent);
			$this->entryManager = new EntryManager($this->_Parent);
			
			// Find members section:
			if (!$this->getSectionId()) return false;
						
			$this->setAccessId($_SESSION['fmm']);
			
			$this->updateTrackingData();
			$this->cleanTrackingData();
			
			return true;
		}
		
		public function parameters(&$context) {
			$this->initialize();
			
			$context['params']['fmm-member-id'] = $this->getMemberId();
		}
		
	/*-------------------------------------------------------------------------
		Tracking:
	-------------------------------------------------------------------------*/
		
		public function getAccessId() {
			if (empty($this->accessId)) {
				$this->setAccessId(md5(time()));
			}
			
			return $this->accessId;
		}
		
		public function setAccessId($access_id) {
			$_SESSION['fmm'] = $this->accessId = $access_id;
			
			return $this;
		}
		
		public function getClientId() {
			if (empty($this->clientId)) {
				$this->clientId = md5($_SERVER['HTTP_USER_AGENT']);
			}
			
			return $this->clientId;
		}
		
		public function cleanTrackingData() {
			$this->_Parent->Database->query("
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
			return (boolean)$this->_Parent->Database->fetchVar('id', 0, "
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
			return $this->_Parent->Database->fetchRow(0, "
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
			
			$this->_Parent->Database->query("
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
				$this->member = @current($this->entryManager->fetch($this->getMemberId(), $this->getSectionId()));
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
				$member_id = $this->_Parent->Database->fetchVar('entry_id', 0, "
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
			$section = $this->getSection();
			
			return @current($section->fetchFields($type));
		}
		
	/*-------------------------------------------------------------------------
		Section:
	-------------------------------------------------------------------------*/
		
		public function getSection() {
			if (is_null($this->section)) {
				$this->section = $this->sectionManager->fetch($this->getSectionId());
			}
			
			return $this->section;
		}
		
		public function getSectionId() {
			if (empty($this->sectionId)) {
				extract($this->_Parent->Database->fetchRow(0, "
					SELECT
						count(*) = 3 AS section_found,
						f.parent_section AS section_id
					FROM
						`tbl_fields` AS f
					WHERE
						f.type IN ('membername', 'memberpassword', 'memberstatus')
					GROUP BY
						f.parent_section
					LIMIT 1
				"));
				
				if ($section_found) @$this->setSectionId($section_id);
			}
			
			return $this->sectionId;
		}
		
		public function setSectionId($section_id) {
			$this->sectionId = (integer)$section_id;
			
			return $this;
		}
	}
	
	class FMM {
		const STATUS_PENDING = 'pending';
		const STATUS_BANNED = 'banned';
		const STATUS_ACTIVE = 'active';
		
		const FIELD_MEMBERNAME = 'membername';
		const FIELD_MEMBERPASSWORD = 'memberpassword';
		const FIELD_MEMBERSTATUS = 'memberstatus';
		
		const TRACKING_NORMAL = 'normal';
		const TRACKING_LOGIN = 'login';
		const TRACKING_LOGOUT = 'logout';
	}
	
?>
