<?php	/* $Id:$ */

// SQL Abstraction Layer for AGI Applications
// Original Release by Rob Thomas (xrobau@gmail.com)
// Copyright Rob Thomas (2009)
//
//    This program is part of FreePBX
//
//    FreePBX is free software: you can redistribute it and/or modify
//    it under the terms of the GNU Affero General Public License as
//    published by the Free Software Foundation, either version 3 of the
//    License, or (at your option) any later version.
//
//    FreePBX is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU Affero General Public License for more details.
//
//    You should have received a copy of the GNU Affero General Public License
//    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
//
//
//  -- Usage --
//  Simply include this file in any AGI scripts that require database access.
//  It automatically determines the type of database being used (MySQL/sqlite)
//  and offers an abstraction layer to access the database.
//
//  Note that this DOES NOT use PEAR. Deliberate design decision, as Pear/DB
//  is depreciated anyway. It shouldn't bother you, and by not using it, we
//  speed things up. Speed is important in an AGI.
//
//  Commands:
//
//  $AGI = new AGI();
//  $db = new AGIDB($AGI);
//
//  $result = $db->sql(...raw sql command...)
//	Returns the result of the SQL command.  This will die noisily if you 
//	try to do something that isn't portable between databases (eg ALTER TABLE 
//	or use CREATE with 'auto_increment') - use the alternate commands below, 
//	or design your database to be portable.
//
//  $result = $db->rename_table($from, $to)
//	Renames a table. Result is not null if an error occured, and the errorstr
//	is in $result.
//
//  $result = $db->add_col($tablename, $colname, $type)
//	Add a column called $col_name of type $type to table $tablename. Result is not
//	null if an error occured, and the errorstr is in $result.
//
//  $result = $db->drop_col($tablename, $colname)
//	Drops a column from table $tablename. Actually drops the column if using MySQL,
//	recreates the table if using SQLite.
//
//  $result = $db->alter_col($tablename, $colname, $type)
//	Changes the type of a column. Changes it directly in MySQL, recreates the table
//	if using SQLite
//

if (!class_exists('AGI')) {
	print "WARNING: AGI Class does not exist. You've probably done something wrong. Read the documentation.\n";
} 

class AGIDB {
  // Database Variables from [globals]. Self explanatory.
  private $dbhost;
  private $dbuser;
  private $dbpass;
  private $dbfile;
  private $dbname;
  
  private $agi; // A copy of the AGI class already running
  private $db;  // 'mysql', 'sqlite' or 'sqlite3'. Set in sql_database_connect, so we
		// know which commands to use. 
  // Just in case someone REALLY wants to work around all the sanity checks here, these
  // two variables are public, so you can use them if you REALLY must.
  public $dbtype;
  public $dbhandle; 

  function AGIDB($AGI) { // This gets called when 'new AGIDB(..)' is run.
	$this->agi = $AGI; // Grab a copy of the AGI class.
	// Load up the variables we'll need later.
	$this->dbtype = $this->get_var("AMPDBENGINE");
	$this->dbhost = $this->get_var("AMPDBHOST");
	$this->dbuser = $this->get_var("AMPDBUSER");
	$this->dbpass = $this->get_var("AMPDBPASS");
	$this->dbfile = $this->get_var("AMPDBFILE");
	$this->dbname = $this->get_var("AMPDBNAME");
	// Don't connect to the database on startup, as you want the AGI
	// to be up and running as fast as possible. Connect on the first
	// SQL command.
	$this->dbhandle = null;
  }

  function sql_database_connect() {
	// Connect to 'the database' - whatever it may be. Return a handle to use.

  	// Determine DB type
	if ($this->dbtype == 'mysql') {
		$dbhandle = mysql_connect($this->dbhost, $this->dbuser, $this->dbpass);
		if (!$dbhandle) {
			$this->debug('SEVERE: AGI could not connect to MySQL Database. '.mysql_error(), 0);
			return null;
		}
		$this->debug("Connected to MySQL database OK.", 4);
		$selected = mysql_select_db($this->dbname, $dbhandle);
		if (!$selected) {
			$this->debug('SEVERE: AGI could not select MySQL Database "'.$this->dbname.'" - '.mysql_error(), 0);
			return null;
		}
		$this->debug("Selected database OK.", 4);
		$this->db = "mysql";
		return $dbhandle;
	} elseif ($this->dbtype == 'sqlite3') {
		// Database is SQLite. It's preferrable to use the inbuilt sqlite_ commands, so
		// check if they exist first.
		if (function_exists('sqlite_open')) {
			$dbhandle = sqlite_open($this->dbfile, 0666, $sqlerr);
			if (!$dbhandle) {
				$this->debug('SEVERE: AGI could not connect to (native) SQLite Database "'.$this->dbfile.'" - '.$sqlerr, 0);
				return null;
			}
			$this->debug("Connected to SQLite database OK (native sqlite).", 4);
			$this->db = "sqlite";
			return $dbhandle;
		// Bugger. OK, We'll have to use php-sqlite3 then. If the module is already loaded, or is
		// compiled in, we'll already have sqlite3_ commands. 
		} elseif (!function_exists('sqlite3_open')) {
			// It's not loaded. Load it.
			dl('sqlite3.so');
		}
		// We now have sqlite3_ functions. Use them!
		$dbhandle = sqlite3_open($this->dbfile);
		if (!$dbhandle) {
			$this->debug('SEVERE: AGI could not connect to (module) SQLite3 Database "'.$this->dbfile.'" - '.sqlite3_error($dbhandle), 0);
			return null;
		}
		$this->debug("Connected to SQLite3 database OK (module sqlite3).", 4);
		return $dbhandle;
		$this->db = "sqlite";
	} else {
		$this->debug('SEVERE: Unknown database type: "'.$this->dbtype.'"', 0);
		return null;
	}
  }

  function sql($command, $override=false) {
	if ($this->dbhandle == null) {
		$this->sql_database_connect();
	}
	// Check for 'ALTER' and die if found
	//
	// else
	//
	// Connect to DB
	$handle = $this->sql_database_connect();
	//
	return true;
  }

  function get_var($value) {
        $r = $this->agi->get_variable( $value );

        if ($r['result'] == 1) {
                $result = $r['data'];
                return $result;
        }
        return '';
  }

  function debug($string, $level=3) {
        $this->agi->verbose($string, $level);
  }

}



?>
