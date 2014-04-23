#!/bin/sh

LANG=C

[ `id -u` -eq '0' ] || die "Must be run as root!"

appname=${0##*/}
date=`date +"%d-%m-%Y"`
time=`date +"%H-%M"`

TEMP=`getopt -n$appname -u \
		--longoptions="db-password: help db-user: backup-loc: retention: zip:" \
		--options="u: p: l: r: z:" \
		"dr:h" "$@"`

eval set -- "$TEMP"

[ $# -eq 2 ] && usage

dbuser="root"
dbpass="NOT_SET"
zippass="NOT_SET"
location="."
retention=""

while [ $# -gt 0 ]
do
	case "$1" in
		--backup-loc|-l)	location=$2;shift;;
		--db-user|-u)		dbuser=$2;shift;;
		--db-password|-p)	dbpass=$2;shift;;
		--retention|-r)		retention=$2;shift;;
		--zip|-z)			zippass=$2;shift;;
		--help|-h)			usage;;
		--)					shift;break;;
		-*)					usage;;
		*)					usage;;
	esac
	shift
done

if [ "$dbpass" = "NOT_SET" ]; then
	dbcreds="-u$dbuser"
else
	dbcreds="-u$dbuser -p$dbpass"
fi

if ! echo | mysql $dbcreds mysql; then
	error "Could not connect to MySQL. Did you forget to add '--db-user=' or '--db-password='?"
	die "Check your credentials or ensure server is running with /etc/init.d/mysqld status"
fi

info "Backup started"

info "Stopping dpp-hub"
/etc/init.d/dpp-hub stop

info "Stopping httpd"
/etc/init.d/httpd stop

info "Dumping MySQL data"
mysqldump $dbcreds --all-databases > mysqldata--$date--$time.sql

if [ "$zippass" = "NOT_SET" ]; then
	`which zip` -9 mysqldata--$date--$time.zip mysqldata--$date--$time.sql
else
	`which zip` -P $zippass -9 mysqldata--$date--$time.zip mysqldata--$date--$time.sql
fi

#These files are copied to a separate folder so that
#during extraction, they don't overwrite anything 
#that may already be required, configured or in use
info "Copying useful stuff"
mkdir server-config
cp -R /etc/httpd ./server-config/
cp /etc/php.ini ./server-config/
cp /etc/php.d/dpp.ini ./server-config/

info "Removing appserver, designtool, pad and hub temp files"
rm -rf /var/opt/dpp-appserver/tmp/*
rm -rf /var/opt/dpp-hub/hub_msgstore/acked/*
rm -rf /var/opt/dpp-designtool/uploads/*
rm -rf /var/opt/dpp-padsupport/tmp/*

info "Obtaining file list of /var/opt"
find /var/opt -type f -exec ls {} \; > backup-list.txt

info "Starting httpd"
/etc/init.d/httpd start

info "Starting dpp-hub"
/etc/init.d/dpp-hub start

info "Obtaining additional files"
find server-config/ -type f -exec ls {} \; >> backup-list.txt

echo "mysqldata--$date--$time.zip" >> backup-list.txt
echo "backup-list.txt" >> backup-list.txt

info "Packaging data - `cat backup-list.txt | wc -l` files"
tar pzcf FormidableBackup--$date--$time.tar.gz -T backup-list.txt

if [ "$location" = "." ] ; then
	info "Removing temp files"
	rm -f mysqldata--$date--$time.sql
	rm -f mysqldata--$date--$time.zip
	rm -f backup-list.txt
	rm -rf server-config/
else
	info "Copying archive to $location"
	cp FormidableBackup--$date--$time.tar.gz $location
	info "Removing temp files"
	rm -f FormidableBackup--$date--$time.tar.gz
	rm -f mysqldata--$date--$time.sql
	rm -f mysqldata--$date--$time.zip
	rm -f backup-list.txt
	rm -rf server-config/
fi

if [ -n "$retention" ] ; then
	if [ $retention -ge 1 ] ; then
		info "Finding and removing files older than $retention days"
		find $location -type f -name FormidableBackup\* -mtime +$retention -exec rm -rfv {} \;
	fi
fi

info "Backup completed"
info "Copy the file to / on the new system and run the command: tar pxzf FormidableBackup--$date--$time.tar.gz"
info "Unzip mysqldata--$date--$time.zip and then import mysqldata--$date--$time.sql into MySQL"
info "Finally, re-run /opt/dpp/postinstall (with --db-password=<your password> if required)"

function usage
{
    echo usage: "$appname [--db-user|-u] [--db-password|-p] [--backup-loc|-l] [--retention|-r]"
    exit 1
}

function info()
{
    echo -e "INFO [`date +"%d-%m-%Y %H-%M"`]: $*";
}

function error()
{
    echo -e "ERROR [`date +"%d-%m-%Y %H-%M"`]: $*" >&2;
}

function die()
{
    echo -e "EXITING [`date +"%d-%m-%Y %H-%M"`]: $*" >&2;
    exit 1
}