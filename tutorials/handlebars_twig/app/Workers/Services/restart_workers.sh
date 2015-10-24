#!/bin/sh

BASEDIR=$(dirname $0)
SUBJECT="Site Workers Restart"
EMAILS="ian@iflist.com,lucasiflist@gmail.com"
THISHOST=$(hostname -f)
PROCESSFILE="notifications_worker.php"
PROCESS="$BASEDIR/$PROCESSFILE"

echo " >>>> Worker Restart Script Starting <<<<< "
echo " >>>> Script is $BASEDIR/notifications_worker.php \n "
echo " >>>> List Command is ps -ef | grep -c $BASEDIR/notifications_worker.php | grep -v \"grep\"  "


# this always returns at least 1 since the grep itself is a process
value=$(ps -ef | grep -c "$BASEDIR/notifications_worker.php" | grep -v "grep" ) 

echo " >>>> Number of processes found : $value "

if [ $value -gt 1 ] 
	then
	# found more than one, so need to stop them
        echo " >>>> ($value) Found. Stopping $BASEDIR/notifications_worker.php "

        # using pgrep we only get the notifications, not the grep call
		result=$( ps -ef | pgrep -lf "$BASEDIR/notifications_worker.php" | awk '{print $1}' | xargs kill )

    echo " >>>> ($result) Done Stopping "

fi	

# now go ahead and start them

echo " >>>> Starting $BASEDIR/notifications_worker.php "

php "$BASEDIR/notifications_worker.php &" 

echo " >>>> ($result) Done Starting "

# mailing program
echo "Letting you know that I had to restart the worker:  $PROCESS . good bye. " | mail -s "[$THISHOST] $SUBJECT "  "IFList Worker Script <noreply@iflist.com>" $EMAILS
 
exit 0

