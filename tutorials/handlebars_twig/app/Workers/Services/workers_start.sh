#!/bin/sh

BASEDIR=$(dirname $0)
SUBJECT="Site Workers Started"
EMAILS="ian@iflist.com,lucasiflist@gmail.com"
THISHOST=$(hostname -f)
PROCESSFILE="notifications_worker.php"
PROCESS="$BASEDIR/$PROCESSFILE"

#echo " >>>> your basedir is $BASEDIR"
#echo " >>>> script is $BASEDIR/notifications_worker.php \n "
#echo "TEST Letting you know that I ...  good bye. " | mail -s "[$THISHOST TEST] testing " -r "IFList Worker Script <noreply@iflist.com>" $EMAILS

#if ps -ef | grep -v grep | "$BASEDIR/notifications_worker.php" ; then

# this always returns at least 1 since the grep itself is a process
value=$(ps -ef | grep -c "$BASEDIR/notifications_worker.php") 

#echo " >>>> Number of processes found : $value "

if [ $value -gt 1 ] 
	then

        #echo " >>>> Not Starting a   $BASEDIR/notifications_worker.php "
        exit 0

	else

        echo " >>>> ($value) Found. Starting $BASEDIR/notifications_worker.php "

        php "$BASEDIR/notifications_worker.php" &
        
        # mailing program
        echo "Letting you know that I had to restart the worker:  $PROCESS . good bye. " | mail -s "[$THISHOST] $SUBJECT "  "IFList Worker Script <noreply@iflist.com>" $EMAILS
        exit 0

fi
