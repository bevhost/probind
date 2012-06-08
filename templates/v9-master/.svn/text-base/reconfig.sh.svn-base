#!/bin/sh
if [ -f deleted_files ]
then
    test -d DELETED || mkdir -p DELETED
	for i in `cat deleted_files`
	do
	    test -f $i && mv -f $i DELETED/ && echo $i moved into DELETED folder
	done
	rm -f deleted_files
fi
/usr/sbin/named-checkconf named.conf
if [ $? = 0 ]
then
 echo "Checkinmg named.conf - OK, named.conf.good renewed"
 cp named.conf named.conf.good
else
 echo "named-checkconf failed; restoring named.conf from named.conf.good"
 test -f named.conf.good && cp named.conf.good named.conf
 exit 1
fi
#
echo /usr/sbin/rndc -c rndc.conf reload
/usr/sbin/rndc -c rndc.conf reload
ko=$?
if [ $ko = 0 ]
then
 echo Server reconfigured
else
 echo Reconfigure failed
fi
sleep 2
test -f /LOGS/WWW/links/errors  && tail -50 /LOGS/WWW/links/errors | grep named
test -f /var/adm/messages && tail -50 /var/adm/messages            | grep named
test -f /var/log/messages && tail -50 /var/log/messages            | grep named
test -f /var/log/syslog   && tail -50 /var/log/syslog              | grep named
exit $ko
