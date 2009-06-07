<?php	/* $Id$ */

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
//  $result = $db->sql($sql, $type)
//	Returns the result of the SQL command $sql.  This will die noisily if you 
//	try to do something that isn't portable between databases (eg ALTER TABLE 
//	or use CREATE with 'auto_increment') - use the alternate commands below, 
//	or design your database to be portable.
//	$type specifies the return type - 
//		ASSOC for an Associative array 
//		NUM for a numeric array. 
//		BOTH for both in the same result
//
//	Note this returns the ENTIRE result. So for example taken from routepermissions:
//
//	$res = $db->sql("SELECT allowed,faildest FROM routepermissions WHERE exten='$cidnum' and routename='$routename'", "BOTH");
//
//	if allowed and faildest return 'NO' and 'ext-vm,300', the result would look like this:
//	$result = {
//			[0] =>  {  
//					[0] = 'NO',		// Only these with 'NUM' type
//					[1] = 'ext-vm,300',
//					'allowed' => 'NO',	// Only these with 'ASSOC' type
//					'faildest => 'ext-vm,300',
//				}
//		  }
//	
//	if ($res[0]['allowed'] == 'NO') {
//		$agi->goto($res[0]['faildest']);
//	}
//
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

  // Public things that you might need to access

  public $errstr; 	// Holder for error string
  public $numrows; 	// Number of rows returned in the query

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
			$this->errstr = 'SEVERE: AGI could not connect to MySQL Database. '.mysql_error();
			debug ($this->errstr, 1);
			return null;
		}
		$this->debug("Connected to MySQL database OK.", 4);
		$selected = mysql_select_db($this->dbname, $dbhandle);
		if (!$selected) {
			$this->errstr = 'SEVERE: AGI could not select MySQL Database "'.$this->dbname.'" - '.mysql_error();
			$this->debug($this->errstr, 1);
			return null;
		}
		$this->debug("Selected database OK.", 4);
		$this->errstr = null;
		$this->db = "mysql";
		return $dbhandle;
	} elseif ($this->dbtype == 'sqlite3') {
		// Database is SQLite. It's preferrable to use the inbuilt sqlite_ commands, so
		// check if they exist first.
		if (function_exists('sqlite_open')) {
			$dbhandle = sqlite_open($this->dbfile, 0666, $sqlerr);
			if (!$dbhandle) {
				$this->errstr = 'SEVERE: AGI could not connect to (native) SQLite Database "'.
					$this->dbfile.'" - '.$sqlerr;
				$this->debug($this->errstr, 1);
				return null;
			}
			$this->debug("Connected to SQLite database OK (native sqlite).", 4);
			$this->errstr = null;
			$this->db = "sqlite";
			return $dbhandle;
		// Bugger. OK, We'll have to use php-sqlite3 then. If the module is already loaded, or is
		// compiled in, we'll already have sqlite3_ commands. 
		} elseif (!function_exists('sqlite3_open')) {
			$this->debug('Loading sqlite3.so', 4);
			// It's not loaded. Load it.
			dl('sqlite3.so');
			$this->debug('Loaded', 4);
		}
		// We now have sqlite3_ functions. Use them!
		$dbhandle = sqlite3_open($this->dbfile);
		if (!$dbhandle) {
			$this->errstr='SEVERE: AGI could not connect to (module) SQLite3 Database "'.
				$this->dbfile.'" - '.sqlite3_error($dbhandle);
			$this->debug($this->errstr, 1);
			return null;
		}
		$this->debug("Connected to SQLite3 database OK (module sqlite3).", 4);
		$this->errstr = null;
		$this->db = "sqlite3";
		return $dbhandle;
	} else {
		$this->errstr = 'SEVERE: Unknown database type: "'.$this->dbtype.'"';
		$this->debug($this->errstr, 1);
		return null;
	}
  }

  function sql($command, $type = "BLANK", $override=false) {

	// Ensure we're connected to the database.
	if ($this->dbhandle == null) {
		$this->dbhandle = $this->sql_database_connect();
	}
	if ($this->dbhandle == null) {
		// We didn't get a valid handle after the connect, so fail.
		$this->debug('SEVERE: Unable to connect to database.', 1);
		return false;
	}
	// Check for non-portable stuff. 
	if ($override != true) {
		$result = $this->sql_check($command);
		// sql_check returns a sanitized SQL command, or false if error.
		if ($result == false) {
			return false;
		}
	} else {
		$result = $command;
	}

	// Check the TYPE
	switch ($type) {
		case "BLANK":
			$this->debug("WARNING: Please provide the type of the query for SQL command '$result'. Defaulting to BOTH", 2);
			$type = "BOTH";
			break;
		case "ASSOC":
		case "NUM":
		case "BOTH":
			break;
		default:
			$this->errstr = "SEVERE: Uknown Query type '$type' for query '$result'";
			$this->debug($this->errstr, 1);
			return false;
	}

	// Actually do the SQL

	var $sqlresult;

	switch ($this->db) {
		case "mysql":
			$res = mysql_query($result, $this->dbhandle);
			if (!$res) {
				$this->errstr = "MySQL Error: ".mysql_error()." with query $result";
				$this->debug($this->errstr, 1);
				return false;
			}
			// Loop through the returned result set, loading it into the array to return
			// to the caller.
			$this->numrows = mysql_num_rows($res);
			// Return the correct type.
			for ($i = 0; $i <= $this->numrows; $i++) {
				if ($type = "NUM") {
					$sqlresult[$i] = mysql_fetch_array($res, MYSQL_NUM);
				} elseif ($type = "ASSOC") {
					$sqlresult[$i] = mysql_fetch_array($res, MYSQL_ASSOC);
				} else {
					$sqlresult[$i] = mysql_fetch_array($res, MYSQL_BOTH);
				}
			}
			return $sqlresult;
		case "sqlite":
			$res = sqlite_query($this->dbhandle, $result, SQLITE_BOTH, $errmsg);
			if (!$res) {
				$this->errstr = "SQLite Error: '$errmsg' with query '$result'";
				$this->debug($this->errstr, 1);
				return false;
			}
			// Loop through the returned result set, loading it into the array to return
			// to the caller.
			$this->numrows = sqlite_num_rows($res);
			// Return the correct type.
			if ($type = "NUM") {
				$sqlresult = sqlite_fetch_all($res, SQLITE_NUM);
			} elseif ($type = "ASSOC") {
				$sqlresult = sqlite_fetch_all($res, SQLITE_ASSOC);
			} else {
				$sqlresult = sqlite_fetch_all($res, SQLITE_BOTH);
			}
			return $sqlresult;
		case "sqlite3":
				


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
