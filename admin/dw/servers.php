<?php
/**
 * /admin/dw/servers.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (C) 2010-2015 Greg Chetcuti <greg@chetcuti.com>
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
include("../../../_includes/start-session.inc.php");
include("../../../_includes/init.inc.php");

require_once(DIR_ROOT . "classes/Autoloader.php");
spl_autoload_register('DomainMOD\Autoloader::classAutoloader');

$error = new DomainMOD\Error();
$system = new DomainMOD\System();
$time = new DomainMOD\Timestamp();

include(DIR_INC . "head.inc.php");
include(DIR_INC . "config.inc.php");
include(DIR_INC . "software.inc.php");
include(DIR_INC . "database.inc.php");

$system->authCheck();
$system->checkAdminUser($_SESSION['is_admin'], $web_root);

$page_title = "Data Warehouse Servers";
$software_section = "admin-dw-manage-servers";

$export_data = $_GET['export_data'];

$sql = "SELECT id, name, host, protocol, port, username, hash, notes, dw_accounts, dw_dns_zones, dw_dns_records, build_end_time, insert_time, update_time
        FROM dw_servers
        ORDER BY name, host";

if ($export_data == "1") {

    $result = mysqli_query($connection, $sql) or $error->outputOldSqlError($connection);

    $export = new DomainMOD\Export();
    $export_file = $export->openFile('dw_servers', strtotime($time->time()));

    $row_contents = array($page_title);
    $export->writeRow($export_file, $row_contents);

    $export->writeBlankRow($export_file);

    $row_contents = array(
        'Name',
        'Host',
        'Protocol',
        'Port',
        'Username',
        'Hash',
        'Notes',
        'DW Accounts',
        'DW DNS Zones',
        'DW DNS Records',
        'DW Last Built',
        'Inserted',
        'Updated'
    );
    $export->writeRow($export_file, $row_contents);

    if (mysqli_num_rows($result) > 0) {

        while ($row = mysqli_fetch_object($result)) {

            $row_contents = array(
                $row->name,
                $row->host,
                $row->protocol,
                $row->port,
                $row->username,
                $row->hash,
                $row->notes,
                $row->dw_accounts,
                $row->dw_dns_zones,
                $row->dw_dns_records,
                $row->build_end_time,
                $row->insert_time,
                $row->update_time
            );
            $export->writeRow($export_file, $row_contents);

        }

    }

    $export->closeFile($export_file);

}
?>
<?php include(DIR_INC . 'doctype.inc.php'); ?>
<html>
<head>
    <title><?php echo $system->pageTitle($software_title, $page_title); ?></title>
    <?php include(DIR_INC . "layout/head-tags.inc.php"); ?>
</head>
<body>
<?php include(DIR_INC . "layout/header.inc.php"); ?>
<?php
$result = mysqli_query($connection, $sql) or $error->outputOldSqlError($connection);

if (mysqli_num_rows($result) > 0) { ?>

    [<a href="servers.php?export_data=1">EXPORT</a>]

    <table class="main_table" cellpadding="0" cellspacing="0">
    <tr class="main_table_row_heading_active">
        <td class="main_table_cell_heading_active">
            <div class="main_table_heading">Name</div>
        </td>
        <td class="main_table_cell_heading_active">
            <div class="main_table_heading">Host</div>
        </td>
        <td class="main_table_cell_heading_active">
            <div class="main_table_heading">Port</div>
        </td>
        <td class="main_table_cell_heading_active">
            <div class="main_table_heading">Username</div>
        </td>
        <td class="main_table_cell_heading_active">
            <div class="main_table_heading">Inserted</div>
        </td>
        <td class="main_table_cell_heading_active">
            <div class="main_table_heading">Updated</div>
        </td>
    </tr><?php

    while ($row = mysqli_fetch_object($result)) { ?>

        <tr class="main_table_row_active">
        <td class="main_table_cell_active">
            <a class="invisiblelink" href="edit/server.php?dwsid=<?php echo $row->id; ?>"><?php echo $row->name; ?></a>
        </td>
        <td class="main_table_cell_active">
            <a class="invisiblelink" href="edit/server.php?dwsid=<?php echo $row->id; ?>"><?php
                echo $row->protocol; ?>://<?php echo $row->host; ?></a>
        </td>
        <td class="main_table_cell_active">
            <a class="invisiblelink" href="edit/server.php?dwsid=<?php echo $row->id; ?>"><?php echo $row->port; ?></a>
        </td>
        <td class="main_table_cell_active">
            <a class="invisiblelink"
               href="edit/server.php?dwsid=<?php echo $row->id; ?>"><?php echo $row->username; ?></a>
        </td>
        <td class="main_table_cell_active">
            <?php if ($row->insert_time == "0000-00-00 00:00:00") $row->insert_time = "-"; ?>
            <a class="invisiblelink"
               href="edit/server.php?dwsid=<?php echo $row->id; ?>"><?php echo $row->insert_time; ?></a>
        </td>
        <td class="main_table_cell_active">
            <?php if ($row->update_time == "0000-00-00 00:00:00") $row->update_time = "-"; ?>
            <a class="invisiblelink"
               href="edit/server.php?dwsid=<?php echo $row->id; ?>"><?php echo $row->update_time; ?></a>
        </td>
        </tr><?php

    } ?>

    </table><?php

} else {

    echo "You don't currently have any servers setup. <a href=\"add/server.php\">Click here to add one</a>.";

}
?>
<?php include(DIR_INC . "layout/footer.inc.php"); ?>
</body>
</html>