#!/bin/sh

BASEDIR="/Library/WebServer/Documents/iflist/iflist-dev.com/app/Workers/Services"
#$(dirname $0)
SUBJECT="Site Workers Stopped on Push"
EMAILS="ian@iflist.com,lucasiflist@gmail.com"
THISHOST=$(hostname -f)
PROCESSFILE="notifications_worker.php"
PROCESS="$BASEDIR/$PROCESSFILE"

# echo "\n your basedir is $BASEDIR"
# echo "script is $BASEDIR/notifications_worker.php \n "

# this always returns at least 1 since the grep itself is a process
# value=$(ps -ef | grep -c "$BASEDIR/notifications_worker.php") 

result=$( ps -ef | grep "$BASEDIR/notifications_worker.php" | grep -v "grep" | awk '{print $2}' | xargs kill )

#mailing program
echo "Letting you know that I Stopped the worker: ' $BASEDIR/notifications_worker.php ' . Good bye. " | mail -s "[$THISHOST] $SUBJECT " "IFList Worker Script <noreply@iflist.com>" $EMAILS

exit 0

