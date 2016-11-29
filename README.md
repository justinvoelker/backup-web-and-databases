# backup-web-and-databases
Simple script to backup a web directory as well as associated MySQL
database(s)

This script performs two main tasks
* Create a backup a given web directory (such as /var/www)
* Create a backup of every database a given user has access to

The first is responsible for creating a compressed tar file of an entire
web directory (either of an entire server or a single website). The
second is responsible for creating a compressed tar file of each
database a given user has access to. For instance, create a database
user with username *db_backup* and give the user permissions to every
database you wish to backup. When new databases need to be backed up
simply give that user access.

As the script is meant to be executed every hour, both the web and
database backups are kept on hourly, daily, and weekly intervals. Here
is how each type of backup works.

Note that the schedules below of Monday and 12:00am are defaults but can
be configured in the config.php file.

### Web Directory Backup

If correctly executed every hour, eventually, four backups will exist.
They are created at different times and are named as follows:
* `backups/web/weekly.tar.gz` Weekly backup taken Monday at 12:00am (00:00)
* `backups/web/daily.tar.gz` Daily backup taken every day at 12:00am (00:00)
* `backups/web/hourly.tar.gz` Hourly backup taken every hour of every day
* `backups/web/hourly-1.tar.gz` Hourly backup from the previous hour

Immediately before the hourly backup is executed, the most recent hourly
backup is saved as `hourly-1.tar.gz`. The protects against changes at
8:59pm that are then captured in the 9:00pm backup with no way of
restoring the changes that were just made a minute prior to the backup.

### Database Backup

Similar to the web directory backups, the name of the backup created
depends on the day of week and hour that the script is executed. The
main difference is that the database backups are stored in a
subdirectory that corresponds to the name of the database. The following
names are for a fictitious database name `example`:
* `backups/database/example/example_weekly.tar.gz` Weekly backup taken Monday at 12:00am (00:00)
* `backups/database/example/example_daily.tar.gz` Daily backup taken every day at 12:00am (00:00)
* `backups/database/example/example_hourly.tar.gz` Hourly backup taken every hour of every day
* `backups/database/example/example_hourly-1.tar.gz` Hourly backup from the previous hour

The subdirectories help organize the database backup directory which can
easily grow out of control with multiple databases. Including the
database name in the backup file name ensure that if copied elsewhere,
there is to be no question about which backup is for which database.

Just as with web directory backups, immediately before the hourly backup
is executed, the most recent hourly backup is saved as
`example_hourly-1.tar.gz`.

## Download

Navigate to the directory where you would like to download the project
and execute a `git clone` to pull the project. This command will create
a new directory named `backup-web-and-databases` which contains the
project (you can rename this directory if desired). Once pulled, use
composer to retrieve project dependencies.

```
cd ~
git clone https://github.com/justinvoelker/backup-web-and-databases.git
cd backup-web-and-databases
composer install --no-dev
```

## Setup

In order for the script to perform, a few configuration settings must be
updated. Copy the sample config file and edit the copy as appropriate.
Some values are required while others can likely be left as needed.

```
cp config/config-sample.php config/config.php
vi config/config.php
```

See additional notes within the config.php file for information on what
values are to be provided.

## Execute

Preferably, the script will be executed as a user with elevated
privileges to have access to everything that needs to be backed up. For
instance, if a website contains files uploaded by guests they will
likely be owned by the user that the web process is running as. You may
or may not have access to copy those files. By executing the backup as
root or some other elevated user, all of a websites files can be backed
up without permissions getting in the way.

The following command will open root's crontab for editing so that the
backup script will be executed as root.

```
sudo crontab -e
```

With that open, add a new line with the following:

```
0 * * * * nice -n 15 /usr/bin/php -q /home/username/backup-web-and-databases/backup.php > /dev/null 2>&1
```

What everything in this command means:
* `0 * * * *` Execute the command every hour, on the hour
* `nice -n 15` Use lower priority for the script to leave resources for
  more important things (like serving up websites)
* `/usr/bin/php` Location of PHP binary (type `whereis php` at a command
  line to find this if you aren't sure)
* `-q` Quiet mode (suppress HTTP header output)
* `/home/username/backup-web-and-databases/backup.php` The location of
  the script to execute (change path if necessary)
* `> /dev/null` Discard standard output from the script
* `2>&1` Redirect error output to standard output (which is discarded)
