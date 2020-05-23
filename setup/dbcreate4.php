<?php

require_once '../evedbconfig.php';
require_once '../eve.class.php';
require_once '../eveuserservice.class.php';
require_once 'textvalues.php';

function create_database_4($screenname, $password)
{
	$errors = array();
	$pref = EveDBConfig::$prefix;
	$mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
	if ($mysqli->error) $errors[] = $mysqli->error;

	$mysqli->set_charset("utf8");

	// TODO rename to submission_definition
	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition` (
		  `id` int(11) NOT NULL,
		  `description` text COLLATE utf8_unicode_ci,
		  `information` text COLLATE utf8_unicode_ci,
		  `requirement` enum('none','after_payment') COLLATE utf8_unicode_ci DEFAULT 'none',
		  `allow_multiple_submissions` tinyint(4) NOT NULL DEFAULT '0',
		  `deadline` datetime,
		  `submission_structure` text COLLATE utf8_unicode_ci,
		  `revision_structure` text COLLATE utf8_unicode_ci,
		  `access_restricted` tinyint(4) NOT NULL DEFAULT '0',
		  `active` tinyint(4) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	if ($mysqli->error) $errors['submission_definition creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition_access` (
		  `id` int(11) NOT NULL,
		  `submission_definition_id` int(11) NOT NULL,
		  `type` enum('specific_user','specific_category','submission_after_deadline') COLLATE utf8_unicode_ci DEFAULT 'specific_user',
		  `content` varchar(255) COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	
	if ($mysqli->error) $errors['submission_definition_access_rule creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}submission_definition_reviewer` (
		  `id` int(11) NOT NULL,
		  `submission_definition_id` int(11) NOT NULL,
		  `email` varchar(255) COLLATE utf8_unicode_ci,
		  `type` varchar(255) COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	
	if ($mysqli->error) $errors['submission_definition_reviewer creation'] = $mysqli->error;

	// TODO rename to submission
	$mysqli->query
	("
		CREATE TABLE `{$pref}submission` (
		  `id` int(11) NOT NULL,
		  `submission_definition_id` int(11) DEFAULT NULL,
		  `structure` text COLLATE utf8_unicode_ci,
		  `email` varchar(255) COLLATE utf8_unicode_ci,
		  `date` datetime,
		  `content` text COLLATE utf8_unicode_ci,
		  `reviewer_email` varchar(255) COLLATE utf8_unicode_ci,
		  `revision_structure` text COLLATE utf8_unicode_ci,
		  `revision_content` text COLLATE utf8_unicode_ci,
		  `revision_status` int(11) NOT NULL DEFAULT '0',
		  `active` tinyint(4) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['submission creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}usercategory` (
		  `id` int(11) NOT NULL,
		  `description` varchar(255) COLLATE utf8_unicode_ci,
		  `special` tinyint(4) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");
	
	if ($mysqli->error) $errors['user_category creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}certification` (
		  `id` int(11) NOT NULL,
		  `certificationdef_id` int(11) DEFAULT NULL,
		  `screenname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `submissionid` int(11) DEFAULT NULL,
		  `locked` int(11) NOT NULL DEFAULT '0',
		  `views` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['certification creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}certificationdef` (
		  `id` int(11) NOT NULL,
		  `type` enum('usercertification','submissioncertification') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'usercertification',
		  `name` text COLLATE utf8_unicode_ci,
		  `pagesize` enum('A3','A4','A5','Letter','Legal') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'A4',
		  `pageorientation` enum('P','L') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'L',
		  `backgroundimage` text COLLATE utf8_unicode_ci,
		  `text` text COLLATE utf8_unicode_ci,
		  `topmargin` smallint(6) NOT NULL DEFAULT '0',
		  `leftmargin` smallint(6) NOT NULL DEFAULT '0',
		  `rightmargin` smallint(6) NOT NULL DEFAULT '0',
		  `text_lineheight` int(11) NOT NULL DEFAULT '0',
		  `text_fontsize` int(11) NOT NULL DEFAULT '0',
		  `hasopenermsg` smallint(6) NOT NULL DEFAULT '0',
		  `openermsg` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['certificationdef creation'] = $mysqli->error;
	
	$mysqli->query
	("
		CREATE TABLE `{$pref}unverifieduser` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `verificationcode` varchar(255) COLLATE utf8_unicode_ci NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['unverified_user creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}pages` (
		  `id` int(11) NOT NULL,
		  `position` smallint(6) NOT NULL DEFAULT '0',
		  `is_visible` tinyint(4) NOT NULL DEFAULT '1',
		  `is_homepage` tinyint(4) NOT NULL DEFAULT '0',
		  `title` text COLLATE utf8_unicode_ci,
		  `content` text COLLATE utf8_unicode_ci,
		  `views` int(11) NOT NULL DEFAULT '0'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['pages creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}payment` (
		  `id` int(11) NOT NULL,
		  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
		  `date` date,
		  `paymenttype_id` int(11) DEFAULT NULL,
		  `value_paid` double NOT NULL DEFAULT '0',
		  `value_received` double NOT NULL DEFAULT '0',
		  `note` text COLLATE utf8_unicode_ci,
		  `image` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['payment creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}paymenttype` (
		  `id` int(11) NOT NULL,
		  `name` text COLLATE utf8_unicode_ci,
		  `description` text COLLATE utf8_unicode_ci,
		  `active` tinyint(11) NOT NULL DEFAULT '1'
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['paymenttype creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}settings` (
		  `key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `value` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['settings creation'] = $mysqli->error;

	$mysqli->query
	("
		CREATE TABLE `{$pref}user` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['user creation'] = $mysqli->error;

	// TODO Move admin to user table
	$mysqli->query
	("
		CREATE TABLE `{$pref}userdata` (
		  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		  `admin` tinyint(4) NOT NULL DEFAULT '0',
		  `locked_form` tinyint(4) NOT NULL DEFAULT '0',
		  `name` text COLLATE utf8_unicode_ci,
		  `address` text COLLATE utf8_unicode_ci,
		  `city` text COLLATE utf8_unicode_ci,
		  `state` text COLLATE utf8_unicode_ci,
		  `country` text COLLATE utf8_unicode_ci,
		  `postalcode` text COLLATE utf8_unicode_ci,
		  `birthday` date,
		  `gender` enum('male','female','rathernotsay') COLLATE utf8_unicode_ci DEFAULT NULL,
		  `phone1` text COLLATE utf8_unicode_ci,
		  `phone2` text COLLATE utf8_unicode_ci,
		  `institution` text COLLATE utf8_unicode_ci,
		  `category_id` int(11) DEFAULT NULL,
		  `customtext1` text COLLATE utf8_unicode_ci,
		  `customtext2` text COLLATE utf8_unicode_ci,
		  `customtext3` text COLLATE utf8_unicode_ci,
		  `customtext4` text COLLATE utf8_unicode_ci,
		  `customtext5` text COLLATE utf8_unicode_ci,
		  `customflag1` int(11) NOT NULL DEFAULT '0',
		  `customflag2` int(11) NOT NULL DEFAULT '0',
		  `customflag3` int(11) NOT NULL DEFAULT '0',
		  `customflag4` int(11) NOT NULL DEFAULT '0',
		  `customflag5` int(11) NOT NULL DEFAULT '0',
		  `note` text COLLATE utf8_unicode_ci
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	");

	if ($mysqli->error) $errors['user_data creation'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition`
		  ADD PRIMARY KEY (`id`);
	");

	if ($mysqli->error) $errors['submission pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_access`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `email` (`content`),
		  ADD KEY `submission_definition_id` (`submission_definition_id`);
	");

	if ($mysqli->error) $errors['submission_definition_access pk fk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_reviewer`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `submission_definition_id` (`submission_definition_id`);
	");

	if ($mysqli->error) $errors['submission_definition_reviewer pk fk'] = $mysqli->error;


	$mysqli->query
	("
		ALTER TABLE `{$pref}submission`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `submission_definition_id` (`submission_definition_id`),
		  ADD KEY `email` (`email`),
		  ADD KEY `reviewer_email` (`reviewer_email`);
	");

	if ($mysqli->error) $errors['submission pk fk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}usercategory`
		  ADD PRIMARY KEY (`id`);
	");

	if ($mysqli->error) $errors['user_category pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}certification`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `certificationdef_id` (`certificationdef_id`),
		  ADD KEY `screenname` (`screenname`),
		  ADD KEY `submissionid` (`submissionid`);
	");

	if ($mysqli->error) $errors['certification pk fk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}certificationdef`
		  ADD PRIMARY KEY (`id`);
	");

	if ($mysqli->error) $errors['certificationdef pk'] = $mysqli->error;
	
	$mysqli->query
	("
		ALTER TABLE `{$pref}unverifieduser`
		  ADD PRIMARY KEY (`email`);
	");
	
	if ($mysqli->error) $errors['unverified_user pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}pages`
		  ADD PRIMARY KEY (`id`);
	");
	
	if ($mysqli->error) $errors['pages pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}payment`
		  ADD PRIMARY KEY (`id`),
		  ADD KEY `paymenttype_id` (`paymenttype_id`),
		  ADD UNIQUE KEY `email` (`email`);
	");

	if ($mysqli->error) $errors['payment pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}paymenttype`
		  ADD PRIMARY KEY (`id`);
	");

	if ($mysqli->error) $errors['paymenttype pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}settings`
		  ADD PRIMARY KEY (`key`);
	");

	if ($mysqli->error) $errors['settings pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}user`
		  ADD PRIMARY KEY (`email`);
	");

	if ($mysqli->error) $errors['user pk'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}userdata`
		  ADD UNIQUE KEY `email` (`email`),
		  ADD KEY `category_id` (`category_id`);
	");
	
	if ($mysqli->error) $errors['user_data pk fk'] = $mysqli->error;

	// Auto increments
	$mysqli->query("ALTER TABLE `{$pref}submission_definition` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}submission_definition_access` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}submission_definition_reviewer` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}submission` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}usercategory` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}certification` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}certificationdef` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}pages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}payment` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	$mysqli->query("ALTER TABLE `{$pref}paymenttype` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;");
	if ($mysqli->error) $errors['autoincrements'] = $mysqli->error;

	// Constraints
	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_access`
		  ADD CONSTRAINT `{$pref}submission_definition_access_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['submission_definition_access_rule constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission_definition_reviewer`
		  ADD CONSTRAINT `{$pref}submission_definition_reviewer_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['submission_definition_reviewer constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}submission`
		  ADD CONSTRAINT `{$pref}submission_ibfk_1` FOREIGN KEY (`submission_definition_id`) REFERENCES `{$pref}submission_definition` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}submission_ibfk_2` FOREIGN KEY (`email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}submission_ibfk_3` FOREIGN KEY (`reviewer_email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['submission constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}certification`
		  ADD CONSTRAINT `{$pref}certification_ibfk_1` FOREIGN KEY (`certificationdef_id`) REFERENCES `{$pref}certificationdef` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}certification_ibfk_2` FOREIGN KEY (`screenname`) REFERENCES `{$pref}user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}certification_ibfk_3` FOREIGN KEY (`submissionid`) REFERENCES `{$pref}submission`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['certification constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}payment`
		  ADD CONSTRAINT `{$pref}payment_ibfk_1` FOREIGN KEY (`email`) REFERENCES `{$pref}user` (`email`) ON DELETE SET NULL ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}payment_ibfk_2` FOREIGN KEY (`paymenttype_id`) REFERENCES `{$pref}paymenttype` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;"
	);
	if ($mysqli->error) $errors['payment constraints'] = $mysqli->error;

	$mysqli->query
	("
		ALTER TABLE `{$pref}userdata`
		  ADD CONSTRAINT `{$pref}userdata_ibfk_1` FOREIGN KEY (`email`) REFERENCES `{$pref}user` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
		  ADD CONSTRAINT `{$pref}userdata_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `{$pref}usercategory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
	");
	if ($mysqli->error) $errors['user_data constraints'] = $mysqli->error;

	// Populating settings
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('block_user_form', 'after_payment');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_border_bg', '{$GLOBALS['color_border_bg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_breadcrumbs_bg', '{$GLOBALS['color_breadcrumbs_bg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_breadcrumbs_fg', '{$GLOBALS['color_breadcrumbs_fg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_button_bg', '{$GLOBALS['color_button_bg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_button_fg', '{$GLOBALS['color_button_fg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_content_bg', '{$GLOBALS['color_content_bg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_content_fg', '{$GLOBALS['color_content_fg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_section_bg', '{$GLOBALS['color_section_bg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('color_section_fg', '{$GLOBALS['color_section_fg']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_bgimage', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_border', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_border_color', '#c0c0c0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_cell_height', '30');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_cell_width', '65');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_cells_per_line', '3');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_countryflag', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_countryflag_h', '20');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_countryflag_w', '20');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_countryflag_x', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_countryflag_y', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_lines_per_page', '8');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_page_left_margin', '10');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_page_orientation', 'P');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_page_size', 'A4');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_page_top_margin', '10');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_textbox', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_textbox_content', '\$user[name]');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_textbox_fontsize', '14');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_textbox_h', '5');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_textbox_w', '55');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_textbox_x', '10');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('credential_textbox_y', '10');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_border_bg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_breadcrumbs_bg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_breadcrumbs_fg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_button_bg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_button_fg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_content_bg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_content_fg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_section_bg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('custom_section_fg', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_msg_certification', '<p>Voc&ecirc; tem um novo certificado dispon&iacute;vel em&nbsp;\$system_name:</p>\r\n<p><strong>\$certification_name</strong></p>\r\n<p>Para acessar e baixar o certificado, acesse&nbsp;<a href=\"\$site_url\">\$site_url</a>,&nbsp;com seu&nbsp;e-mail e senha cadastrados.</p>\r\n<p>Se encontrar dificuldades, entre em contato conosco no e-mail \$support_email_address.</p>')");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_sbj_certification', 'Novo certificado dispon&iacute;vel - \$system_name')");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_snd_certification', '1')");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_msg_password_retrieval', '<p>Sua senha para \$system_name foi redefinida.</p>\r\n<ul>\r\n<li>Seu e-mail: \$email</li>\r\n<li>Sua senha: \$password</li>\r\n</ul>\r\n<p><span>Acesse&nbsp;</span><a href=\"\$site_url\">\$site_url</a><span>&nbsp;com&nbsp;estes dados e, para sua maior seguran&ccedil;a, altere sua senha imediatamente.</span></p>\r\n<p>Caso tenha dificuldade em acessar o sistema, entre em contato conosco no e-mail \$support_email_address.</p>');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_sbj_password_retrieval', 'Recuperação de senha - \$system_name');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_msg_payment', '<p>As informa&ccedil;&otilde;es de pagamento para o usu&aacute;rio \$email em \$system_name foram atualizadas.</p>\r\n<p>Seu pagamento consta como <strong>\$paymenttype_name</strong>.</p>\r\n<p>\$paymenttype_description</p>\r\n<p>Se voc&ecirc; tiver d&uacute;vidas a respeito do pagamento, entre em contato conosco no e-mail \$support_email_address.</p>');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_sbj_payment', 'Pagamento Atualizado - \$system_name');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_snd_payment', '1');");	
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_msg_reviewer', '<p>Uma submissão foi atribuida a sua avaliação em \$system_name.</p>\r\n<p><strong>Detalhes da submiss&atilde;o</strong></p>\r\n<p>\$submission_content</p>\r\n<p>Para atribuir nota, escrever coment&aacute;rios e finalizar a revis&atilde;o, entre em \$site_url com seu e-mail (\$email) e senha cadastrados. Caso n&atilde;o saiba sua senha, acesse a op&ccedil;&atilde;o \"esqueci minha senha\".</p>\r\n<p>Para outras d&uacute;vdas, entre em contato atrav&eacute;s do e-mail \$support_email_address.</p>');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_sbj_reviewer', 'Submissão para avaliação - \$system_name');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_snd_reviewer', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_msg_revision', '<p>Sua submiss&atilde;o enviada em \$system_name foi avaliada</p>\r\n<p><strong>Detalhes da submiss&atilde;o</strong></p>\r\n<p>\$submission_content</p>\r\n<p><strong>Avalia&ccedil;&atilde;o</strong></p>\r\n<p>\$revision_content</p>\r\n<p>Para mais informa&ccedil;&otilde;es,&nbsp;entre em \$site_url com seu e-mail (\$email) e senha cadastrados. Caso n&atilde;o saiba sua senha, acesse a op&ccedil;&atilde;o \"esqueci minha senha\".</p>\r\n<p>Para outras d&uacute;vdas, entre em contato atrav&eacute;s do e-mail \$support_email_address.</p>');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_sbj_revision', 'Submissão avaliada - \$system_name');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_snd_revision', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_msg_submission', '<p>Este e-mail confirma o envio de sua submissão em \$system_name.</p>\r\n<p><strong>Detalhes da submiss&atilde;o</strong></p>\r\n<p>\$submission_content</p>\r\n<p>Para mais informações, entre em contato atrav&eacute;s do e-mail \$support_email_address.</p>');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_sbj_submission', 'Submissão enviada - \$system_name');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_snd_submission', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_msg_user_verification', '<p>Esta &eacute; uma mensagem de verifica&ccedil;&atilde;o do e-mail \$email para o acesso a \$system_name.</p>\r\n<p>Para confirmar seu e-mail e acessar o sistema, clique no link a seguir:</p>\r\n<ul>\r\n<li><a href=\"\$verification_url\">\$verification_url</a></li>\r\n</ul>\r\n<p>Caso n&atilde;o consiga acessar o link acima diretamente, acesse \$site_url, digite o e-mail e senha cadastradas e quando for pedido o c&oacute;digo de verifica&ccedil;&atilde;o, insira o c&oacute;digo a seguir:</p>\r\n<ul>\r\n<li>\$verification_code</li>\r\n</ul>\r\n<p>Caso encontre dificuldades para acessar o sistema, entre em contato conosco por e-mail: \$support_email_address.</p>');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_sbj_user_verification', 'Verificação de e-mail - \$system_name');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_msg_welcome', '<p>Esta mensagem confirma o cadastro para \$system_name. O e-mail \$email foi verificado.</p>\r\n<p>Voc&ecirc; j&aacute; pode acessar&nbsp;<a href=\"\$site_url\">\$site_url</a>, com seu&nbsp;e-mail e senha cadastrados.</p>\r\n<p>Por motivo de seguran&ccedil;a, as senhas s&atilde;o criptografadas e n&atilde;o temos acesso a elas. Caso voc&ecirc; se esque&ccedil;a ou n&atilde;o tenha sua senha, acesse a op&ccedil;&atilde;o esqueci minha senha, e uma nova senha ser&aacute; enviada para seu e-mail.</p>\r\n<p>Se voc&ecirc; tiver dificuldades em acessar o sistema ou em continuar com sua inscri&ccedil;&atilde;o, entre em contato conosco no e-mail \$support_email_address.</p>');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('email_sbj_welcome', 'Bem-vindo - \$system_name');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('payment_closed', '0');");	
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('payment_information_unverified', '');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('payment_information_verified', '');");	
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_address', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_birthday', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_categorydescription', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_city', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_country', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customflag1', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customflag2', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customflag3', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customflag4', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customflag5', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customtext1', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customtext2', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customtext3', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customtext4', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_customtext5', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_email', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_gender', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_institution', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_name', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_phone1', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_phone2', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_pmtdate', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_pmtid', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_pmtnote', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_pmttype', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_pmtvaluepaid', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_pmtvaluereceived', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_postalcode', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_export_visible_state', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_address', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_birthday', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_categorydescription', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_city', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_country', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customflag1', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customflag2', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customflag3', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customflag4', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customflag5', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customtext1', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customtext2', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customtext3', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customtext4', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_customtext5', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_email', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_gender', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_institution', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_name', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_phone1', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_phone2', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_pmtdate', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_pmtid', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_pmtnote', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_pmttype', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_pmtvaluepaid', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_pmtvaluereceived', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_postalcode', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('paymentlisting_screen_visible_state', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('phpmailer_fromname', '{$GLOBALS['phpmailer_fromname']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('phpmailer_host', '{$GLOBALS['phpmailer_host']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('phpmailer_password', '{$GLOBALS['phpmailer_password']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('phpmailer_port', '{$GLOBALS['phpmailer_port']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('phpmailer_smtpauth', '{$GLOBALS['phpmailer_smtpauth']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('phpmailer_smtpdebug', '{$GLOBALS['phpmailer_smtpdebug']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('phpmailer_smtpsecure', '{$GLOBALS['phpmailer_smtpsecure']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('phpmailer_username', '{$GLOBALS['phpmailer_username']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('show_content_menu_and_pages', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('show_footer', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('show_header_image', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('show_header_text', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('support_email_address', '{$GLOBALS['support_email_address']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('system_custom_login_message', '{$GLOBALS['system_custom_login_message']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('system_custom_login_message_text', '{$GLOBALS['system_custom_login_message_text']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('system_custom_message', '{$GLOBALS['system_custom_message']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('system_custom_message_title', '{$GLOBALS['system_custom_message_title']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('system_custom_message_text', '{$GLOBALS['system_custom_message_text']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('system_currency', 'BRL');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('system_locale', 'pt_BR');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('system_name', '{$GLOBALS['system_name']}');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_address_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_address_visible', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_birthday_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_birthday_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_category_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_category_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_city_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_city_visible', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_country_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_country_visible', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_custom_message_on_unlocked_form', '');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag1_label', 'Op&ccedil;&atilde;o personalizada 1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag1_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag2_label', 'Op&ccedil;&atilde;o personalizada 2');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag2_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag3_label', 'Op&ccedil;&atilde;o personalizada 3');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag3_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag4_label', 'Op&ccedil;&atilde;o personalizada 4');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag4_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag5_label', 'Op&ccedil;&atilde;o personalizada 5');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customflag5_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext1_label', 'Texto personalizado 1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext1_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext1_mask', NULL);");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext1_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext2_label', 'Texto personalizado 2');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext2_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext2_mask', NULL);");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext2_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext3_label', 'Texto personalizado 3');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext3_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext3_mask', NULL);");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext3_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext4_label', 'Texto personalizado 4');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext4_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext4_mask', NULL);");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext4_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext5_label', 'Texto personalizado 5');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext5_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext5_mask', NULL);");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_customtext5_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_display_custom_message_on_unlocked_form', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_gender_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_gender_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_institution_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_institution_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_name_mandatory', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_name_visible', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_phone1_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_phone1_visible', '1');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_phone2_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_phone2_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_postalcode_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_postalcode_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_signup_closed', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_signup_closed_message', '<p>Inscri&ccedil;&otilde;es encerradas</p>');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_state_mandatory', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('user_state_visible', '0');");
	$mysqli->query ("INSERT INTO `{$pref}settings` (`key`, `value`) VALUES ('userarea_label', '{$GLOBALS['userarea_label']}');");
	if ($mysqli->error) $errors['settings values'] = $mysqli->error;	
	
	$eve = new Eve();
	$eveUserServices = new EveUserServices($eve);
	$encryptedPassword = $eveUserServices->encrypt($password);
	$eveUserServices->createUser($screenname, $encryptedPassword, false);
	$eveUserServices->setUserAsAdmin($screenname);
	return $errors;
}

function erase_database_4()
{
	$errors = array();
	$pref = EveDBConfig::$prefix;
	$mysqli = new mysqli(EveDBConfig::$server, EveDBConfig::$user, EveDBConfig::$password, EveDBConfig::$database);
	if ($mysqli->error) $errors['Connection error'] = $mysqli->error;

	// Deleting free tables
	$mysqli->query("DROP TABLE if exists `{$pref}settings`;");
	if ($mysqli->error) $errors['settings delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}pages`;");
	if ($mysqli->error) $errors['pages delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}unverifieduser`;");
	if ($mysqli->error) $errors['unverified_user delete error'] = $mysqli->error;

	// Deleting payment tables
	$mysqli->query("DROP TABLE if exists `{$pref}payment`;");
	if ($mysqli->error) $errors['payment delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}paymenttype`;");
	if ($mysqli->error) $errors['paymenttype delete error'] = $mysqli->error;

	// Deleting certification tables
	$mysqli->query("DROP TABLE if exists `{$pref}certification`;");
	if ($mysqli->error) $errors['certification delete error'] = $mysqli->error;	
	$mysqli->query("DROP TABLE if exists `{$pref}certificationdef`;");
	if ($mysqli->error) $errors['certificationdef delete error'] = $mysqli->error;

	// Deleting submission tables
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition_access`;");
	if ($mysqli->error) $errors['submission_definition_access_rule delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition_reviewer`;");
	if ($mysqli->error) $errors['submission_definition_reviewer delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission`;");
	if ($mysqli->error) $errors['submission delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}submission_definition`;");
	if ($mysqli->error) $errors['submission_definitions delete error'] = $mysqli->error;

	// Deleting user tables
	$mysqli->query("DROP TABLE if exists `{$pref}userdata`;");
	if ($mysqli->error) $errors['user_data delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}usercategory`;");
	if ($mysqli->error) $errors['user_category delete error'] = $mysqli->error;
	$mysqli->query("DROP TABLE if exists `{$pref}user`;");
	if ($mysqli->error) $errors['user delete error'] = $mysqli->error;

	return $errors;
}

?>
