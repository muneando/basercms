<?php

/* SVN FILE: $Id: mail_input.ctp 250 2011-12-08 10:15:44Z arata $ */
/**
 * [MOBILE] メールフィールド
 *
 * PHP versions 5
 *
 * baserCMS :  Based Website Development Project <http://basercms.net>
 * Copyright 2008 - 2013, baserCMS Users Community <http://sites.google.com/site/baserusers/>
 *
 * @copyright		Copyright 2008 - 2013, baserCMS Users Community
 * @link			http://basercms.net baserCMS Project
 * @package			baser.plugins.mail.views
 * @since			baserCMS v 0.1.0
 * @version			$Revision$
 * @modifiedby		$LastChangedBy$
 * @lastmodified	$Date$
 * @license			http://basercms.net/license/index.html
 */
if (!isset($blockStart)) {
	$blockStart = 0;
}
if (!isset($blockEnd)) {
	$blockEnd = null;
}
$data = array(
	'blockStart' => $blockStart,
	'blockEnd' => $blockEnd
);
$this->BcBaser->includeCore('Mail.Elements/mobile/mail_input', $data);
