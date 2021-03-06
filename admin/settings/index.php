<?php
/**
 * /admin/settings/index.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2018 Greg Chetcuti <greg@chetcuti.com>
 *
 * Project: http://domainmod.org   Author: http://chetcuti.com
 *
 * DomainMOD is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * DomainMOD is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with DomainMOD. If not, see
 * http://www.gnu.org/licenses/.
 *
 */
?>
<?php
require_once __DIR__ . '/../../_includes/start-session.inc.php';
require_once __DIR__ . '/../../_includes/init.inc.php';
require_once DIR_INC . '/config.inc.php';
require_once DIR_INC . '/software.inc.php';
require_once DIR_ROOT . '/vendor/autoload.php';

$deeb = DomainMOD\Database::getInstance();
$form = new DomainMOD\Form();
$layout = new DomainMOD\Layout();
$system = new DomainMOD\System();
$time = new DomainMOD\Time();

require_once DIR_INC . '/head.inc.php';
require_once DIR_INC . '/debug.inc.php';
require_once DIR_INC . '/settings/admin-settings.inc.php';

$system->authCheck();
$system->checkAdminUser($_SESSION['s_is_admin']);
$pdo = $deeb->cnxx;

$new_full_url = $_POST['new_full_url'];
$new_email_address = $_POST['new_email_address'];
$new_expiration_days = $_POST['new_expiration_days'];
$new_large_mode = $_POST['new_large_mode'];
$new_use_smtp = $_POST['new_use_smtp'];
$new_smtp_server = $_POST['new_smtp_server'];
$new_smtp_protocol = $_POST['new_smtp_protocol'];
$new_smtp_port = $_POST['new_smtp_port'];
$new_smtp_email_address = $_POST['new_smtp_email_address'];
$new_smtp_username = $_POST['new_smtp_username'];
$new_smtp_password = $_POST['new_smtp_password'];
$new_debug_mode = $_POST['new_debug_mode'];
$new_local_php_log = $_POST['new_local_php_log'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $new_email_address != "" && $new_full_url != "" && $new_expiration_days != "") {

    $stmt = $pdo->prepare("
        UPDATE settings
        SET full_url = :new_full_url,
            email_address = :new_email_address,
            large_mode = :new_large_mode,
            use_smtp = :new_use_smtp,
            smtp_server = :new_smtp_server,
            smtp_protocol = :new_smtp_protocol,
            smtp_port = :new_smtp_port,
            smtp_email_address = :new_smtp_email_address,
            smtp_username = :new_smtp_username,
            smtp_password = :new_smtp_password,
            expiration_days = :new_expiration_days,
            debug_mode = :new_debug_mode,
            local_php_log = :new_local_php_log,
            update_time = :timestamp");
    $stmt->bindValue('new_full_url', $new_full_url, PDO::PARAM_STR);
    $stmt->bindValue('new_email_address', $new_email_address, PDO::PARAM_STR);
    $stmt->bindValue('new_large_mode', $new_large_mode, PDO::PARAM_INT);
    $stmt->bindValue('new_use_smtp', $new_use_smtp, PDO::PARAM_INT);
    $stmt->bindValue('new_smtp_server', $new_smtp_server, PDO::PARAM_STR);
    $stmt->bindValue('new_smtp_protocol', $new_smtp_protocol, PDO::PARAM_STR);
    $stmt->bindValue('new_smtp_port', $new_smtp_port, PDO::PARAM_STR);
    $stmt->bindValue('new_smtp_email_address', $new_smtp_email_address, PDO::PARAM_STR);
    $stmt->bindValue('new_smtp_username', $new_smtp_username, PDO::PARAM_STR);
    $stmt->bindValue('new_smtp_password', $new_smtp_password, PDO::PARAM_STR);
    $stmt->bindValue('new_expiration_days', $new_expiration_days, PDO::PARAM_INT);
    $stmt->bindValue('new_debug_mode', $new_debug_mode, PDO::PARAM_INT);
    $stmt->bindValue('new_local_php_log', $new_local_php_log, PDO::PARAM_INT);
    $timestamp = $time->stamp();
    $stmt->bindValue('timestamp', $timestamp, PDO::PARAM_STR);
    $stmt->execute();

    $_SESSION['s_system_full_url'] = $new_full_url;
    $_SESSION['s_system_email_address'] = $new_email_address;
    $_SESSION['s_system_large_mode'] = $new_large_mode;
    $_SESSION['s_system_expiration_days'] = $new_expiration_days;
    $_SESSION['s_system_local_php_log'] = $new_local_php_log;

    $_SESSION['s_message_success'] .= "The System Settings were updated<BR>";

    header("Location: index.php");
    exit;

} else {

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        if ($new_full_url == "") $_SESSION['s_message_danger'] .= "Enter the full URL of your " . SOFTWARE_TITLE . " installation<BR>";
        if ($new_email_address == "") $_SESSION['s_message_danger'] .= "Enter the system email address<BR>";
        if ($new_expiration_days == "") $_SESSION['s_message_danger'] .= "Enter the number of days to display in expiration emails<BR>";

    } else {

        $result = $pdo->query("
            SELECT full_url, email_address, large_mode, use_smtp, smtp_server, smtp_protocol, smtp_port,
                smtp_email_address, smtp_username, smtp_password, expiration_days, debug_mode, local_php_log
            FROM settings")->fetch();

        if ($result) {

            $new_full_url = $result->full_url;
            $new_email_address = $result->email_address;
            $new_large_mode = $result->large_mode;
            $new_use_smtp = $result->use_smtp;
            $new_smtp_server = $result->smtp_server;
            $new_smtp_protocol = $result->smtp_protocol;
            $new_smtp_port = $result->smtp_port;
            $new_smtp_email_address = $result->smtp_email_address;
            $new_smtp_username = $result->smtp_username;
            $new_smtp_password = $result->smtp_password;
            $new_expiration_days = $result->expiration_days;
            $new_debug_mode = $result->debug_mode;
            $new_local_php_log = $result->local_php_log;

        }

    }
}
?>
<?php require_once DIR_INC . '/doctype.inc.php'; ?>
<html>
<head>
    <title><?php echo $system->pageTitle($page_title); ?></title>
    <?php require_once DIR_INC . '/layout/head-tags.inc.php'; ?>
</head>
<body class="hold-transition skin-red sidebar-mini">
<?php require_once DIR_INC . '/layout/header.inc.php'; ?>
<?php
echo $form->showFormTop('');
echo $form->showInputText('new_full_url', 'Full ' . SOFTWARE_TITLE . ' URL (100)', 'Enter the full URL of your ' . SOFTWARE_TITLE . ' installation, excluding the trailing slash (Example: http://example.com/domainmod)', $new_full_url, '100', '', '1', '', '');
echo $form->showInputText('new_email_address', 'System Email Address (100)', 'This should be a valid email address that is monitored by the ' . SOFTWARE_TITLE . ' System Administrator. It will be used in various system locations, such as the REPLY-TO address for emails sent by ' . SOFTWARE_TITLE . '.', $new_email_address, '100', '', '1', '', '');
echo $form->showInputText('new_expiration_days', 'Expiration Days to Display', 'This is the number of days in the future to display on the Dashboard and in expiration emails.', $new_expiration_days, '3', '', '1', '', '');
echo $form->showRadioTop('Large Mode', 'If you have a very large database and your main Domain page is loading slowly, enabling Large Mode will fix the issue, at the cost of losing some of the advanced filtering and mobile functionality. You should only need to enable this if your database contains upwards of 10,000 domains.', '');
echo $form->showRadioOption('new_large_mode', '1', 'Enabled', $new_large_mode, '<BR>', '&nbsp;&nbsp;&nbsp;&nbsp;');
echo $form->showRadioOption('new_large_mode', '0', 'Disabled', $new_large_mode, '', '');
echo $form->showRadioBottom('');
echo $form->showRadioTop('Debugging Mode', 'Unless you\'re having problems with ' . SOFTWARE_TITLE . ' and support has asked you to turn this on, you should leave it turned off.', '');
echo $form->showRadioOption('new_debug_mode', '1', 'Enabled', $new_debug_mode, '<BR>', '&nbsp;&nbsp;&nbsp;&nbsp;');
echo $form->showRadioOption('new_debug_mode', '0', 'Disabled', $new_debug_mode, '', '');
echo $form->showRadioBottom('');
echo $form->showRadioTop('Local PHP Log', 'This allows you to log PHP errors in a local file called domainmod.log, instead of recording them in the main PHP log.<BR>' .
    $layout->highlightText('red', 'WARNING:') . ' Only enable this feature temporarily for troubleshooting, and if you\'re asked to by ' . SOFTWARE_TITLE . ' support. Leaving it enabled all the time will cause logged errors to be visible to everyone who knows the URL to your ' . SOFTWARE_TITLE . ' installation, which could allow them to compromise your system.', '');
echo $form->showRadioOption('new_local_php_log', '1', 'Enabled', $new_local_php_log, '<BR>', '&nbsp;&nbsp;&nbsp;&nbsp;');
echo $form->showRadioOption('new_local_php_log', '0', 'Disabled', $new_local_php_log, '', '');
echo $form->showRadioBottom('');
?>
<div class="box box-default collapsed-box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title" style="padding-top: 3px;">
            <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i></button>&nbsp;SMTP Server Settings
        </h3>
    </div>
    <div class="box-body"><?php
        echo $form->showRadioTop('Use SMTP Server?', "If the instance of PHP running on your " . SOFTWARE_TITLE . " server isn't configured to send mail, you can use an external SMTP server to send system emails.", '');
        echo $form->showRadioOption('new_use_smtp', '1', 'Yes', $new_use_smtp, '<BR>', '&nbsp;&nbsp;&nbsp;&nbsp;');
        echo $form->showRadioOption('new_use_smtp', '0', 'No', $new_use_smtp, '', '');
        echo $form->showRadioBottom('');
        echo $form->showInputText('new_smtp_server', 'SMTP Server (255)', 'If you plan on using an external SMTP server, enter the server name here.', $new_smtp_server, '100', '', '', '', '');
        echo $form->showRadioTop('SMTP Server Protocol', '', '');
        echo $form->showRadioOption('new_smtp_protocol', 'tls', 'TLS', $new_smtp_protocol, '<BR>', '&nbsp;&nbsp;&nbsp;&nbsp;');
        echo $form->showRadioOption('new_smtp_protocol', 'ssl', 'SSL', $new_smtp_protocol, '', '');
        echo $form->showRadioBottom('');
        echo $form->showInputText('new_smtp_port', 'SMTP Server Port (5)', '', $new_smtp_port, '5', '', '', '', '');
        echo $form->showInputText('new_smtp_email_address', 'SMTP Email Address (100)', '', $new_smtp_email_address, '100', '', '', '', '');
        echo $form->showInputText('new_smtp_username', 'SMTP Username (100)', 'This is usually the same as the SMTP Email Address.', $new_smtp_username, '100', '', '', '', '');
        echo $form->showInputText('new_smtp_password', 'SMTP Password (255)', '', $new_smtp_password, '255', '', '', '', ''); ?>
    </div>
</div><BR><?php

echo $form->showSubmitButton('Update System Settings', '', '');
echo $form->showFormBottom('');
?>
<?php require_once DIR_INC . '/layout/footer.inc.php'; ?>
</body>
</html>
