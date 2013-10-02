PHP CBEADS README

This document describes how to install CBEADS.


Requirements (these have been used for development):

PHP 5.3.1
Mysql 5.0.5
Apache 2.2.14

It is assumed these components are already installed. Using a package such as XAMPP makes it easy to set up such an enviroment.


Installation:

1. The Files

	The archive contains this folder structure:

	|--[application]
	|--[system]
	|--[web]
	|-- db_dump.sql
	|-- license
	\-- readme.txt

	In the web folder is the index.php file which is the entry point into the system.
	You can put these files anywhere as long as the web folder is accessible.

	For example, taking a XAMPP installation, where a folder called cbeads is created in the htdocs folder, the resultant file structure is:
	[htdocs]
	  \--[cbeads]
		   |--[application]
		   |--[system]
		   |--[web]
		   |-- db_dump.sql
		   |-- license
		   \-- readme.txt

	   
2. Creating the Database

	Once the files are in place, the database has to be set up by running the db_dump.sql file. 
	XAMPP comes with phpmyadmin which allows the sql to be run in a web interface.

	The databases that will be created are called:
	 - cbeads
	 - sboml
	 - dcs
	 
	Please ensure that there are no existing databases with those names.


3. Adjusting Config Files

	Under the /application/config folder there are various config files that may need editing.

	3.a)  config.php contains these various configuration items which are important in getting CBEADS to run properly. Please go through the config file and look at each item. Most can be left as they are, however some will need to be changed.

	The most important ones are:

	$config['base_url'] = "http://localhost/cbeads/web";
	- base_url should be set to the url that points to the folder where the index.php is located. In a basic XAMPP setup where htdocs contains a folder called 'cbeads' that contain the system files, the url can be left as is.
	
	$config['base_web_url'] = "http://localhost/cbeads/web";
	- base_web_url will usually be the same as base_url. It exists for cases where the index.php file is located in a different place to the web resources.
	
	$config['cbeads_installation_name'] = 'My CBEADS Server';
	- cbeads_installation_name defines the name given to this cbeads installation. It is used on the login page.


	
	3.b)  database.php contains the values used for connecting to the database:

	$db['cbeads']['hostname'] = "127.0.0.1";
	$db['cbeads']['username'] = "root";
	$db['cbeads']['password'] = "pass";

	These values can be changed as needed. If the values are incorrect an error will be displayed as the system needs database access. 


4. Using the System

	When the above steps have been covered, test the system by entering the url needed for accessing the cbeads web folder.
	In a basic XAMPP setup, the url will be http://localhost/cbeads/web/. This should display the login page.

	To log in the username is 'admin' and the password is 'admin'.

	Once logged a menu should appear on the left with a bar at the top. The contents of the current application appear in the main area on the screen.
	Functions are organised by application in the menu. Clicking on a menu group will collapse/expand it.

	To log out, click the 'Log Out' text that can be found on the top right of the screen.

	You will find three applications that can be used:

	CBEADS: This is used for managing CBEADS related information, such as the user list and the applications and functions that exist.
	SBOML: This application contains functions for viewing the existing database structure and for editing it.
	Dental Clinic System: This is a sample application that can be played with.

	New applications can be created using the 'Manage Applications' function in the CBEADS menu group.


5. File Access

	Putting all the folders directly into the htdocs folder means that the application and system folders can be accessed from the server. This may not be a good thing to do. Ideally only the web folder should be accessible from outside. There are various ways that only the web folder can be made accessible, such as using .htaccess files, modifying the httpd.conf file or placing the folders in a different location and making a symbolic link from the htdocs folder to the web folder.