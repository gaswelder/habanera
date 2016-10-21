# Site setup

This section is for those who need to install an existing site that
uses the framework.


## The framework and the application directories

The framework itself comes as a folder that has to be copied somewhere
with read-only permissions. It is up to the administrator to decide
where to put the files. For example, the files might be put into the
`/usr/lib/habanera` directory.

The application directory contains the actual site application.
Let's assume that the directory's name is `/home/webapps/app`.

The web server process needs only read permission for the application
files, so the entire directory may be owned by a different user
(probably the one maintaining the site).

The only exception is the "writable directory" to which the webserver
will need to have full permissions. By default it is the `tmp`
directory inside the application directory; in our example that would
be `/home/webapps/app/tmp`. The administrator will have to create and
explicitly grant permissions for it. The administrator can also
redefine the write directory to be somewhere else, like
`/home/webapps/app-data`, but that directory shouldn't be in the `/tmp`
directory as some of the data written by an application may be
non-temporary in nature.


## Static content

Static content like images, stylesheets and javascripts will be, as
usual, in the web root directory. There are no specific recommendations
for that expect the usual ones: assign the files to a different user
than the webserver and give the server only read permissions.

If the site allows uploading files into directories in the web tree
(like weblogs, for example, do), then those directories will have to
have write permissions for the web server. Again, the recommendations
are the same as with the other similar setups.


## Entry point

The entry point is the PHP script that will actually be called by the
web server for all URLs related to the application. All that script has
to do is to include the framework code and call `hmain` with
appropriate arguments.

In our example from above that would be:

	<?php
	require '/usr/lib/habanera/init.php';
	hmain('/home/webapps/app');
	?>

Before calling `hmain` the script might also do some other setup
operations, for example setting the `error_log` directive, setting the
time zone or dealing with idiosyncrasies of the hosting.

The web server has to be configured in such a way as to call
the script for all related URLs and serve the output. Typical
case, especially on a shared hosting, is the Apache web server
and `.htaccess` file with the following content:

	RewriteEngine	On
	RewriteCond	%{DOCUMENT_ROOT}/%{REQUEST_URI} !-f
	RewriteCond	%{DOCUMENT_ROOT}/%{REQUEST_URI} !-d
	RewriteCond	%{REQUEST_URI}	!favicon\.ico
	RewriteRule	.	index.php
