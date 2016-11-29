<?php

/* Require vendor autoload */
require(__DIR__ . '/vendor/autoload.php');

writeLog('initialize');

/* Require the config file */
$config = require(__DIR__ . '/config/config.php');

/* Pull config properties into local variables */
$script_directory = $config['script_directory'];
$web_directory = $config['web_directory'];
$daily_backup_at_hour = $config['daily_backup_at_hour'];
$weekly_backup_on_day = $config['weekly_backup_on_day'];
$database_host = $config['database_host'];
$database_username = $config['database_username'];
$database_password = $config['database_password'];
$timezone = $config['timezone'];
$databases_to_ignore = $config['databases_to_ignore'];
$chown_to = $config['chown_to'];
$send_email = $config['send_email'];
$email_address = $config['email_address'];
$email_subject = $config['email_subject'];
$email_use_smtp = $config['email_use_smtp'];
$smtp_host = $config['smtp_host'];
$smtp_port = $config['smtp_port'];
$smtp_security = $config['smtp_security'];
$smtp_username = $config['smtp_username'];
$smtp_password = $config['smtp_password'];

/* Additional properties to be used throughout the script */
$directory_permissions = 770;
$directory_backups = $script_directory . 'backups/';
$directory_logs = $script_directory . 'logs/';

/* Open the log file for current year and month */
$log_name = $directory_logs . date("Ym") . '.log';
$log_file = fopen($log_name, 'a');
/* Variable for logging messages of this current execution */
$log = [];
/* Command to be executed that will copy files or execute database backup commands */
$cmd = '';

/* Output capture for exec commands. Nothing is done with this information at the moment. */
$output = '';
/* Return status of executed command. Used to determine if the command was successful. */
$return_var = 0;
/* Running log of error encountered. Used to determine success of failure of entire script. */
$error_log = [];

/* Set default timezone to ensure proper date/time when writing to log */
date_default_timezone_set($timezone);

/**
 * Write to the log file.
 * @param string $msg Message to be written to log
 * @param bool $error Whether or not this should also be logged to error_log
 */
function writeLog($msg, $error = false)
{
    /* User variables of global scope instead of new variable in local scope */
    global $log_file, $log, $error_log;
    /* Message to be written */
    $message = '[' . date("Y-m-d H:i:s T") . '] ' . $msg;
    /* Write to log file on filesystem */
    fwrite($log_file, $message . "\n");
    /* Append to running log for this execution */
    array_push($log, $message);
    /* Also append to error_log if necessary */
    if ($error) {
        array_push($error_log, $message);
    }
}

/**
 * Execute a command and return boolean success
 * @param string $command Command to be executed
 * @return bool Whether or not command was successful
 */
function executeCommand($command)
{
    $output = '';
    $return_var = '';
    exec($command, $output, $return_var);

    return $return_var == 0;
}

/*
 * Web directory backup
 */

/* Change working directory to system root to create backups using relative paths */
chdir('/');

/* Before creating hourly backup, copy hourly to hourly-1 */
if (file_exists($directory_backups . 'web/hourly.tar.gz')) {
    $cmd = 'cp -p ' . $directory_backups . 'web/hourly.tar.gz ' . $directory_backups . 'web/hourly-1.tar.gz';
    if (executeCommand($cmd)) {
        writeLog('web - web/hourly.tar.gz copied to web/hourly-1.tar.gz');
    } else {
        writeLog('web - error copying web/hourly.tar.gz to web/hourly-1.tar.gz', true);
    }
}

/* Execute actual backup of web directory (stripping off first slash of absolute path) */
$cmd = 'tar czf ' . $directory_backups . 'web/hourly.tar.gz ' . substr($web_directory, 1);
if (executeCommand($cmd)) {
    writeLog('web - created web/hourly.tar.gz');
} else {
    writeLog('web - error creating web/hourly.tar.gz', true);
}

/* Based on specified hour, this is a daily backup, copy hourly to daily. */
if (date("H") == $daily_backup_at_hour) {
    $cmd = 'cp -p ' . $directory_backups . 'web/hourly.tar.gz ' . $directory_backups . 'web/daily.tar.gz';
    if (executeCommand($cmd)) {
        writeLog('web - web/hourly.tar.gz copied to web/daily.tar.gz');
    } else {
        writeLog('web - error copying web/hourly.tar.gz to web/daily.tar.gz', true);
    }
}

/* Based on specified hour and day, this is a weekly backup, copy hourly to weekly. */
if (date("H") == $daily_backup_at_hour && date("w") == $weekly_backup_on_day) {
    $cmd = 'cp -p ' . $directory_backups . 'web/hourly.tar.gz ' . $directory_backups . 'web/weekly.tar.gz';
    if (executeCommand($cmd)) {
        writeLog('web - web/hourly.tar.gz copied to web/weekly.tar.gz');
    } else {
        writeLog('web - error copying web/hourly.tar.gz to web/weekly.tar.gz', true);
    }
}

/*
 * Database backups
 */

/* Connect to database (log message if trouble connecting) */
$mysqli = new mysqli($database_host, $database_username, $database_password);
if ($mysqli->connect_errno) {
    writeLog('database - error connecting to database (' . $mysqli->connect_errno . ')', true);
}

/* Build array of databases to backup (every database the user has access to) */
$databases = [];
if ($result_sdb = $mysqli->query("SHOW DATABASES")) {
    /* Loop through each returned result */
    while ($row = $result_sdb->fetch_object()) {
        /* If not in the list of databases to ignore, include it */
        if (!in_array($row->Database, $databases_to_ignore)) {
            $databases[] = $row->Database;
        }
    }
} else {
    writeLog('database - error executing SHOW DATABASES', true);
}

/* Change working directory to script directory to create backups using relative paths */
chdir($directory_backups);

/* Loop through each database */
foreach ($databases as $database) {
    /* File paths/names for the various database backups */
    $database_hourly = 'database/' . $database . '/' . $database . '_hourly.tar.gz';
    $database_hourly_1 = 'database/' . $database . '/' . $database . '_hourly-1.tar.gz';
    $database_daily = 'database/' . $database . '/' . $database . '_daily.tar.gz';
    $database_weekly = 'database/' . $database . '/' . $database . '_weekly.tar.gz';

    /* Create individual database subdirectory if it does not exist */
    if (!file_exists('database/' . $database)) {
        mkdir('database/' . $database, octdec($directory_permissions));
    }

    /* Before creating hourly backup, copy hourly to hourly-1 */
    if (file_exists($database_hourly)) {
        $cmd = 'cp -p ' . $database_hourly . ' ' . $database_hourly_1;
        if (executeCommand($cmd)) {
            writeLog('database - ' . $database_hourly . ' copied to ' . $database_hourly_1);
        } else {
            writeLog('database - error copying ' . $database_hourly . ' to ' . $database_hourly_1, true);
        }
    }

    /* Execute actual backup of database */
    $cmd = '/usr/bin/mysqldump -h ' . $database_host . ' -u ' . $database_username . ' -p\'' . $database_password . '\' ' . $database . ' | gzip > ' . $database_hourly;
    if (executeCommand($cmd)) {
        writeLog('database - created ' . $database_hourly . ' via mysqldump');
    } else {
        writeLog('database - error creating ' . $database_hourly . ' via mysqldump', true);
    }

    /* Based on specified hour, this is a daily backup, copy hourly to daily. */
    if (date("H") == $daily_backup_at_hour) {
        $cmd = 'cp -p ' . $database_hourly . ' ' . $database_daily;
        if (executeCommand($cmd)) {
            writeLog('database - ' . $database_hourly . ' copied to ' . $database_daily);
        } else {
            writeLog('database - error copying ' . $database_hourly . ' to ' . $database_daily, true);
        }
    }

    /* Based on specified hour and day, this is a weekly backup, copy hourly to weekly. */
    if (date("H") == $daily_backup_at_hour && date("w") == $weekly_backup_on_day) {
        $cmd = 'cp -p ' . $database_hourly . ' ' . $database_weekly;
        if (executeCommand($cmd)) {
            writeLog('database - ' . $database_hourly . ' copied to ' . $database_weekly);
        } else {
            writeLog('database - error copying ' . $database_hourly . ' to ' . $database_weekly, true);
        }
    }
}

/*
 * Change ownership of files. Since the cron task is running as root, everything backed up should be set back to a
 * non-root user.
 */
$cmd = 'chown -R ' . $chown_to . ':' . $chown_to . ' ' . $directory_backups . ' ' .  $directory_logs;
if (executeCommand($cmd)) {
    writeLog("finalize - successfully executed chown of backups and logs directories");
} else {
    writeLog("finalize - error executing chown of backups and logs directories", true);
}

/*
 * Send email of script result
 */
if ($send_email) {
    /* Does the error_log contain any errors? */
    $script_result = (empty($error_log)) ? "result[Success]" : "result[Failure]";

    /* Create the message */
    $subject = $email_subject . ' ' . $script_result;
    $body = implode("\n", $log) . "\n";
    $body .= "\n";
    $body .= "Error Log:\n";
    $body .= (empty($error_log)) ? 'No errors' : implode("\n", $error_log);

    /* Use smtp for email if desired. Else use mail */
    if ($email_use_smtp) {
        $transport = Swift_SmtpTransport::newInstance(
            $smtp_host,
            $smtp_port,
            $smtp_security)
            ->setUsername($smtp_username)
            ->setPassword($smtp_password);
        $mailer = Swift_Mailer::newInstance($transport);
        $message = Swift_Message::newInstance('swiftmailer')
            ->setFrom([$smtp_username => $smtp_username])
            ->setTo([$email_address])
            ->setSubject($subject)
            ->setBody($body);
        if ($mailer->send($message)) {
            writeLog("finalize - successfully sent script result email via smtp");
        } else {
            writeLog("finalize - error sending script result email via smtp");
        }
    } else {
        /* Send the message and write success/failure to log */
        if (mail($email_address, $subject, $body)) {
            writeLog("finalize - successfully sent script result email");
        } else {
            writeLog("finalize - error sending script result email");
        }
    }
}

/* Close log file */
fclose($log_file);
