<?php

/*
 * For more details, see https://github.com/justinvoelker/backup-web-and-databases
 */

return [

    /* Directory where the backup script is found (absolute path) */
    'script_directory' => '/home/username/backup-web-and-databases/',

    /* Web directory to be backed up (absolute path) */
    'web_directory' => '/var/www/',

    /* At which hour should the daily backup take place? '00' 12am through '23' 11pm (default is '00') */
    'daily_backup_at_hour' => '00',
    /* On which day of the week should the weekly backup take place? '0' Sunday through '6' Saturday (default is '1') */
    'weekly_backup_on_day' => '1',

    /* Database connection settings for the user that has access to all database to be backed up */
    /* User should have LOCK TABLES and SELECT permissions */
    'database_host' => 'localhost',
    'database_username' => 'backup',
    'database_password' => 'password',

    /* Timezone to be used when writing to the log file. Possible values: http://php.net/manual/en/timezones.php */
    'timezone' => 'UTC',

    /* List of databases to ignore. For example, databases that ever user has access to that cannot be removed */
    'databases_to_ignore' => [
        'information_schema',
    ],

    /* After backup is complete, the script will set the follow user as the owner of the files */
    'chown_to' => 'username_here',

    /* Optionally send an email report of execution results (set to true or false) */
    'send_email' => true,

    /* Recipient of execution results */
    'email_address' => 'support@example.com',

    /* If sending an email, set the subject. Appended to this subject will be 'result[Success]' or 'result[Failure]' */
    'email_subject' => 'Web and Database Backup of example.com',

    /* Email host configuration (if not set to use smtp, will send via "mail" call) */
    'email_use_smtp' => false,
    'smtp_host' => 'localhost',
    'smtp_port' => '465',
    'smtp_security' => 'ssl',
    'smtp_username' => 'username_here',
    'smtp_password' => 'password_here',
];
