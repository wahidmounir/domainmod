<?php
/**
 * /classes/DomainMOD/Email.php
 *
 * This file is part of DomainMOD, an open source domain and internet asset manager.
 * Copyright (c) 2010-2017 Greg Chetcuti <greg@chetcuti.com>
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
//@formatter:off
namespace DomainMOD;

class Email
{

    public function sendExpirations($dbcon, $software_title, $from_cron)
    {
        $time = new Time();
        $timestamp_basic = $time->timeBasic();
        $timestamp_long = $time->timeLong();

        list($full_url, $from_address, $number_of_days, $use_smtp) = $this->getSettings($dbcon);
        $send_to = $this->getRecipients($dbcon);
        $subject = "Upcoming Expirations - " . $timestamp_long;
        $headers = $this->getHeaders($software_title, $from_address);

        list($result_domains, $result_ssl) = $this->checkExpiring($dbcon, $number_of_days, $from_cron);
        $message_html = '';
        $message_html .= $this->messageTopHtml($software_title, $full_url, $subject, $number_of_days);
        $message_html .= $this->showDomainsHtml($result_domains, $full_url, $timestamp_basic);
        $message_html .= $this->showSslHtml($result_ssl, $full_url, $timestamp_basic);
        $message_html .= $this->messageBottomHtml($software_title, $full_url);

        list($result_domains, $result_ssl) = $this->checkExpiring($dbcon, $number_of_days, $from_cron);
        $message_text = '';
        $message_text = $subject . "\n\n";
        $message_text .= $this->messageTopText($software_title, $number_of_days);
        $message_text .= $this->showDomainsText($result_domains, $timestamp_basic);
        $message_text .= $this->showSslText($result_ssl, $timestamp_basic);
        $message_text .= $this->messageBottomText($software_title, $full_url);

        while ($row_recipients = mysqli_fetch_object($send_to)) {

            $full_to = '"' . $row_recipients->first_name . ' ' . $row_recipients->last_name . '"' . ' <' . $row_recipients->email_address . '>';

            if ($use_smtp != '1') {

                mail($full_to, $subject, $message_html, $headers, '-f' . $from_address);

            } else {

                $smtp = new Smtp();
                $smtp->send($dbcon, $from_address, $row_recipients->email_address, $row_recipients->first_name
                    . ' ' . $row_recipients->last_name, $subject, $message_html, $message_text);

            }
            sleep(2);
        }
    }

    public function getSettings($dbcon)
    {
        $sql = "SELECT full_url, email_address, expiration_days, use_smtp FROM settings";
        $result = mysqli_query($dbcon, $sql);
        $url = '';
        $email = '';
        $days = '';
        while ($row = mysqli_fetch_object($result)) {
            $url = $row->full_url;
            $email = $row->email_address;
            $days = $row->expiration_days;
            $use_smtp = $row->use_smtp;
        }
        return array($url, $email, $days, $use_smtp);
    }

    public function checkExpiring($dbcon, $days, $from_cron)
    {
        $system = new System();
        $time = new Time();
        $date = $time->timeBasicPlusDays($days);

        $sql_domains = "SELECT id, expiry_date, domain
                        FROM domains
                        WHERE active NOT IN ('0', '10')
                          AND expiry_date <= '" . $date . "'
                        ORDER BY expiry_date, domain";
        $domains_expiring = $system->checkForRowsResult($dbcon, $sql_domains);

        $sql_ssl = "SELECT sslc.id, sslc.expiry_date, sslc.name, sslt.type
                    FROM ssl_certs AS sslc, ssl_cert_types AS sslt
                    WHERE sslc.type_id = sslt.id
                      AND sslc.active NOT IN ('0')
                      AND sslc.expiry_date <= '" . $date . "'
                    ORDER BY sslc.expiry_date, sslc.name";
        $ssl_expiring = $system->checkForRowsResult($dbcon, $sql_ssl);

        if ($domains_expiring != '0' || $ssl_expiring != '0') {
            $_SESSION['s_message_success'] .= 'Expiration Email Sent<BR>';
            return array($domains_expiring, $ssl_expiring);
        } else {
            $_SESSION['s_message_success'] .= 'No Upcoming Expirations<BR>';
            if ($from_cron == '1') exit;
            return false;
        }
    }

    public function getRecipients($dbcon)
    {
        $sql_recipients = "SELECT u.email_address, u.first_name, u.last_name
                           FROM users AS u, user_settings AS us
                           WHERE u.id = us.user_id
                             AND u.active = '1'
                             AND us.expiration_emails = '1'";
        $result_recipients = mysqli_query($dbcon, $sql_recipients);

        if (mysqli_num_rows($result_recipients) <= 0) {
            $_SESSION['s_message_danger'] .= 'No Users Are Subscribed<BR>';
            return false;
        }
        return $result_recipients;
    }

    public function getHeaders($software_title, $from_address)
    {
        $headers = '';
        $headers .= 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        $headers .= 'From: "' . $software_title . '" <' . $from_address . ">\r\n";
        $headers .= 'Return-Path: ' . $from_address . "\r\n";
        $headers .= 'Reply-to: ' . $from_address . "\r\n";
        $version = phpversion();
        $headers .= 'X-Mailer: PHP/' . $version . "\r\n";
        return $headers;
    }

    public function messageTopHtml($software_title, $full_url, $subject, $number_of_days)
    {
        ob_start(); ?>
        <html>
        <head><title><?php echo $subject; ?></title></head>
        <body bgcolor="#FFFFFF">
        <table width="100%" cellspacing="0" cellpadding="0" border="0" bgcolor="#FFFFFF">
        <tr>
        <td width="100%" bgcolor="#FFFFFF">
        <font color="#000000" size="2" face="Verdana, Arial, Helvetica, sans-serif">
        <a title="<?php echo $software_title; ?>" href="<?php echo $full_url; ?>/"><img border="0" alt="<?php
            echo $software_title; ?>" src="<?php echo $full_url; ?>/images/logo.png"></a><BR><BR>Below is a
        list of all the Domains & SSL Certificates in <?php echo $software_title; ?> that are expiring in the next
        <?php echo $number_of_days; ?> days.<BR> <BR>If you would like to change the frequency of this email
        notification please contact your <?php echo $software_title; ?> administrator.<BR><BR><?php
        return ob_get_clean();
    }

    public function messageTopText($software_title, $number_of_days)
    {
        $message = "Below is a list of all the Domains & SSL Certificates in " . $software_title . " that are expiring in the next " . $number_of_days . " days.\n\nIf you would like to change the frequency of this email notification please contact your " . $software_title . " administrator.\n\n";
        return $message;
    }

    public function showDomainsHtml($result_domains, $full_url, $timestamp_basic)
    {
        ob_start();
        if ($result_domains != '0') { ?>
            <strong><u>Domains</u></strong><BR><?php
            while ($row_domains = mysqli_fetch_object($result_domains)) {
                if ($row_domains->expiry_date < $timestamp_basic) { ?>

                    <font color="#CC0000"><?php echo $row_domains->expiry_date; ?></font>&nbsp;&nbsp;<a
                        href="<?php echo $full_url; ?>/edit/domain.php?did=<?php echo $row_domains->id;
                        ?>"><?php echo $row_domains->domain; ?></a>&nbsp;&nbsp;<font
                        color="#CC0000">*EXPIRED*</font><BR><?php
                } else { ?>

                    <?php echo $row_domains->expiry_date; ?>&nbsp;&nbsp;<a href="<?php echo $full_url;
                    ?>/edit/domain.php?did=<?php echo $row_domains->id; ?>"><?php echo $row_domains->domain;
                        ?></a><BR><?php
                }
            }
        }
        return ob_get_clean();
    }

    public function showDomainsText($result_domains, $timestamp_basic)
    {
        if ($result_domains != '0') {
            $message .= "[DOMAINS]\n";
            while ($row_domains = mysqli_fetch_object($result_domains)) {
                if ($row_domains->expiry_date < $timestamp_basic) {
                    $message .= $row_domains->expiry_date . " - " . $row_domains->domain . " *EXPIRED*\n";
                } else {
                    $message .= $row_domains->expiry_date . " - " . $row_domains->domain . "\n";
                }
            }
            $message .= "\n";
        }
        return $message;
    }

    public function showSslHtml($result_ssl, $full_url, $timestamp_basic)
    {
        ob_start();
        if ($result_ssl != '0') { ?>
            <BR><strong><u>SSL Certificates</u></strong><BR><?php
            while ($row_ssl = mysqli_fetch_object($result_ssl)) {
                if ($row_ssl->expiry_date < $timestamp_basic) { ?>
                    <font color="#CC0000"><?php echo $row_ssl->expiry_date; ?></font>&nbsp;&nbsp;<a
                        href="<?php echo $full_url; ?>/edit/ssl-cert.php?sslcid=<?php echo $row_ssl->id;
                        ?>"><?php echo $row_ssl->name; ?> (<?php echo $row_ssl->type; ?>)</a>&nbsp;&nbsp;<font
                        color="#CC0000">*EXPIRED*</font><BR><?php
                } else { ?>
                    <?php echo $row_ssl->expiry_date; ?>&nbsp;&nbsp;<a href="<?php echo $full_url;
                    ?>/edit/ssl-cert.php?sslcid=<?php echo $row_ssl->id; ?>"><?php echo $row_ssl->name; ?>
                        (<?php echo $row_ssl->type; ?>)</a><BR><?php
                }
            }
        }
        return ob_get_clean();
    }

    public function showSslText($result_ssl, $timestamp_basic)
    {
        if ($result_ssl != '0') {
            $message .= "[SSL CERTIFICATES]\n";
            while ($row_ssl = mysqli_fetch_object($result_ssl)) {
                if ($row_ssl->expiry_date < $timestamp_basic) {
                    $message .= $row_ssl->expiry_date . " - " . $row_ssl->name . " (" . $row_ssl->type . ") *EXPIRED*\n";
                } else {
                    $message .= $row_ssl->expiry_date . " - " . $row_ssl->name . " (" . $row_ssl->type . ")\n";
                }
            }
            $message .= "\n";
        }
        return $message;
    }

    public function messageBottomHtml($software_title, $full_url)
    {
        ob_start(); ?>
        <BR>Best Regards,<BR><BR>Greg Chetcuti<BR><a
            target="_blank" href="mailto:greg@domainmod.org">greg@domainmod.org</a><BR>
        </font>
        </td></tr>
        </table>
        <table width="575" cellspacing="0" cellpadding="0" border="0" bgcolor="#FFFFFF"><tr>
        <td width="100%"><font color="#000000" size="2" face="Verdana, Arial, Helvetica, sans-serif">
        <BR><hr width="100%" size="2" noshade>You've received this email because you're currently subscribed to receive
        expiration notifications from the <?php echo $software_title; ?> installation located at: <a target="_blank"
        href="<?php echo $full_url; ?>/"><?php echo $full_url; ?>/</a><BR><BR>To unsubscribe from these notifications
        please visit: <BR><a target="_blank" href="<?php echo $full_url; ?>/settings/email/"><?php echo $full_url;
        ?>/settings/email/</a><BR><BR></font>
        </td></tr>
        </table>
        </body>
        </html><?php
        return ob_get_clean();
    }

    public function messageBottomText($software_title, $full_url)
    {
        $message .= "Best Regards,\n";
        $message .= "\n";
        $message .= "Greg Chetcuti\n";
        $message .= "greg@domainmod.org\n\n";
        $message .= "---\n\n";
        $message .= "You've received this email because you're currently subscribed to receive expiration notifications from the " . $software_title . " installation located at: " . $full_url . "\n\n";
        $message .= "To unsubscribe from these notifications please visit: " . $full_url . "/settings/email/";
        return $message;
    }

} //@formatter:on
