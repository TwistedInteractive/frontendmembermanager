<?php
	
	class Extension_MemberManager extends Extension {
		public function about() {
			return array(
				'name'			=> 'Member Manager',
				'version'		=> '1.003',
				'release-date'	=> '2009-01-06',
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
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_grouplink`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_grouptype`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_membername`");
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_memberpassword`");
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
				CREATE TABLE IF NOT EXISTS `tbl_fields_grouplink` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`field_id` int(11) unsigned NOT NULL,
					`section_id` int(11) unsigned NOT NULL,
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
					`strength` enum(
						'weak', 'good', 'strong'
					) NOT NULL DEFAULT 'good',
					`salt` varchar(255) default NULL,
					PRIMARY KEY (`id`),
					KEY `field_id` (`field_id`)
				)
			");
		}
	}
	
?>
