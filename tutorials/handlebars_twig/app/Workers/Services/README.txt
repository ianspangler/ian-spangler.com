How the worker_start works:

there is a cron job 

	*/2 * * * * /bin/sh [PATH TO THE WEBROOT]/app/Workers/Services/workers_start.sh

	calls thw workers_start shell script every 2 minutes.

	That script checks to see if "[PATH TO THE WEBROOT] /app/Workers/Services/notifications_worker.php" is running

	this will start the worker for notifications (NotificationWorker.php)

	PATH TO THE WEBROOT is different on each site

	The Cron job will actually have 3 parts:
	*/2 * * * * /bin/sh [PATH TO THE DEV WEBROOT]/app/Workers/Services/workers_start.sh
	*/2 * * * * /bin/sh [PATH TO THE TEST1 WEBROOT]/app/Workers/Services/workers_start.sh
	*/2 * * * * /bin/sh [PATH TO THE TEST2 WEBROOT]/app/Workers/Services/workers_start.sh

	// and production
	*/2 * * * * /bin/sh [PATH TO THE PROD WEBROOT]/app/Workers/Services/workers_start.sh

How the worker_stop works

 Called from push.iflist.com when new code is pushed to the directory