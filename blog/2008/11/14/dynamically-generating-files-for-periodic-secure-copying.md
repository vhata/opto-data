--- 
title: Dynamically generating files for periodic secure copying
date: 2008-11-14 12:20:24
--- 
Here is the situation:

Machine A needs to periodically fetch MySQL dumps from machine B, process them, and produce some report.  A fairly trivial problem, and I could think of a number of ways do this, but they were all slightly inelegant:

* **Periodically create the dump on machine B, and have A fetch it periodically and process it.** I didn't like this because the fetch is not triggered by the dump, so there's no sequentiality - you're not guaranteed that A will fetch it immediately when, and only when, B has finished creating the dump.
* **Periodicially create the dump on machine B and push it to machine A, which then periodically processes it.** This has the same problem - the processing is not triggered when the data arrives on A. I really needed the dump, the transfer and the processing to happen sequentially, triggered by each other.

The obvious thing to do is:

* **Have machine A ssh to machine B and run a script that creates the dump, and then scp the dump back and process it.**

However, since I'm automating this to run periodically, I need to set up a passphrase-less key for machine A to ssh to machine B, and I don't like having those lying around unrestricted.  Fortunately, ssh has a mechanism to restrict a key: you can add the 'command=""' option to a key, which means that when using that keypair to ssh to a machine, you will only be able run that specific command.  So, having written a script to perform the database dump on machine B, I restricted machine A's key so that it can only run that script when it connects to B.

But now we have a further problem:  since machine A can only run the dump script when ssh'ing to B, it can no longer scp the dump back to itself (since 'scp' runs over ssh, it won't be able to do its thing, because it is restricted to running the dump script only).  We could of course, set up another keypair with a different restriction, although it's not trivial to work out what "command" to allow in order to accept scp - the normal way would be to create a new account, and give it the 'scponly' shell, but now we have a whole new account, and...  Do you see where this is going?  It just gets more and more complicated and basically inelegant.  It's easily doable, but it's just messy.

I was discussing this with [Michael Gorven][], and realised that even though the command restriction means that machine A will always run the dump script when it connects to machine B, that doesn't mean I can't put stuff in the dump script that lets machine A also do the scp.  When a machine tries to run a different command using a restricted key, the original command is just run anyway, but the attempted command is passed in, via the SSH_ORIGINAL_COMMAND variable. So, I thought, I could examine that, see if it was an scp command, and manually run it if it was.  This was going to cause security problems, though, because I would have to check exactly what the command was, and might miss some devious ways of getting around my checks and running some other command.

Finally, Michael suggested that I not bother making the distinction between ssh'ing in to do the dump, and connecting in to retrieve it via scp.  Just put both steps in one, assume machine A is trying to scp the file, and create and pass back the dump over scp.  The way to serve a file over scp is "scp -f $filename" - normal users never see this because it is handled behind the scenes by scp.  So here is the final script (with certain details left out):

    #!/bin/bash
    
    FNAM=/tmp/dump.$$.sql
    mysqldump DB1 Table1 > $FNAM
    mysqldump DB2 Table2 >> $FNAM
    bzip2 $FNAM
    scp -f $FNAM.bz2
    rm $FNAM.bz2

Now, no matter how machine A connects to machine B, this script will be run, and unless machine A is running its side of the scp protocol, the dialog will fail.  An amusing extra is that no matter what file machine A is trying to retrieve, my database dump will be generated on the fly and sent to it:

    $ scp -i mykey_dsa machineB:something.txt
    dump.2835.sql.bz2       100%     6909KB  1.2MB/s  00:03

I thought that was cute, and I wanted to share it.

[Michael Gorven]: http://michael.gorven.za.net/
