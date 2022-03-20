####  frank@frankmayer.net (7-Feb-2012)

:) Great !! Thanks for your work in this. Much appreciated!!

***

#### ben.swinburne (12-Mar-2012)

Thanks for your quick feedback and continued efforts with this project.

***

#### lucian.toza (2-Apr-2012)

Thanks for the quick reply and the quick fix also :) it works now

***

####  h.leithner (3-May-2012)

Works fine now thx

***

#### akayami (7-May-2012)

Thank you for this tool, it's really great piece of software that opens many possibilities for me. PHP was i need of a good SQL parser for a long time.
 
***

#### roborg (9-May-2012)

I've attached some pretty minor changes that (on my machine at least) make it over 10x faster... hope you find it useful - thanks for a great project!

***

#### korso3 (9-May-2012)

Many thanks on all your effort and fantastic answer.

***

#### adrian.partl (28-Aug-2012)

cool, seems to work. thx!

***

#### Marco64Th (30-Aug-2012)

It is very useful in the project i was working on.

The project was about modifying an existing open source software package (over 2.000 PHP files, almost 500 MySQL tables). The goal was to use the software (single installation/database) for multiple clients, where the data of each client needed to be totally seperated from other clients (no data leaks).

In order to achieve this most tables had to get a new client_id column and all queries needed to be modified to use the client_id when inserting new data or in the WHERE clause when retrieving data, etc..

Doing this the old fashioned way by going over all sources and editing all queries would be undoable and would probable have led to missed queries and by that data leaks. Fortunately the software was using 1 (actually 2) low-level routines for all access to the MySQL database. So the solution was to intercept all queries before they get executed, parse them, modify them, rebuild them and then execute.

The PHP-SQL parser & Creator gave me a good starting point for that.

***

#### vincent.vatelot (10-Sep-2012)

Works fine with /tags/2012-07-03 version. I use it in CodeIgniter very easily. Good work! :)

***

#### nababx (3-Feb-2013)

Great job! It would be even better if you had it on packagist... :) 

***

#### noisecapella (21-Nov-2013)

Thanks! I wanted to say that this has been very useful for my work and I appreciate the effort put into this. I use it to automatically filter, sort and paginate our SQL queries, and so far it works great!

***

#### phoffmann.plus (13-Jan-2014)

Wow, probably the fastest fix I've ever seen, thank you!

***

#### Henk.Blokpoel.Sr (22-Feb-2014)

Hi, thanks a lot! You guys are good & fast. Regards, Henk

***

#### Denis Morozov (05-Apr-2014)

Parser works like a charm! :)
Thank you!

***
