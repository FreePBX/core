<?php	

/* $Id$ */

// SQL Abstraction Layer for AGI Applications
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
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
//  $result = $db->escape($sql)
//	Escapes any characters that could confuse the database and lead to SQL injection problems.
//	You should use this on ANY browser-supplied or user-supplied input.
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
	print "WARNING: AGI Class does not exist. You've probably done something wrong.\n";
	print "Running in debug mode..\n";
	if (class_exists('SQLite3')) { print "SQLite3 Class exists\n"; }
	$db = new AGIDB(null);
	// Using sqlite_master crashes php-sqlite3
	$db->alter_col('trunks', 'failscript', 'VARCHAR (20)');
} 

class AGIDB {
  // Database Variables from [globals]. Self explanatory.
  private $dbhost;
  private $dbuser;
  private $dbpass;
  private $dbfile;
  private $dbname;

  // sqlite3 needs some global variables to handle returns. They aren't needed
  // to be defined here, but be aware that they are used by this module.

  /* global $sql3holderAssoc;	*/
  /* global $sql3holderNum; 	*/
  /* global $sql3holderRowNbr;	*/

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

  function AGIDB($AGI=null) { 
	// This gets called when 'new AGIDB(..)' is run.

	if (!class_exists('AGI')) {
		// Running from the command line.. Hardcode everything, don't
		// use AGI
		$this->dbtype = 'sqlite3';
		$this->dbfile = '/var/lib/asterisk/freepbx.db';
		$this->dbhost = 'localhost';
		$this->dbuser = 'asterisk';
		$this->dbpass = 'asterisk';
		$this->dbname = 'asterisk';
		$this->agi = null;
	} else {
		$this->agi = $AGI; // Grab a copy of the AGI class.
		// Load up the variables we'll need later.
		$this->dbtype = $this->get_var("AMPDBENGINE");
		$this->dbhost = $this->get_var("AMPDBHOST");
		$this->dbuser = $this->get_var("AMPDBUSER");
		$this->dbpass = $this->get_var("AMPDBPASS");
		$this->dbfile = $this->get_var("AMPDBFILE");
		$this->dbname = $this->get_var("AMPDBNAME");
	}
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
		// Database is SQLite. It's preferrable to use the inbuilt PHP5 sqlite3 class, so
		// check if that exist first. Requires PHP 5.3.0
		if (class_exists('SQLite3')) {
			$dbhandle = new SQLite3($this->dbfile, SQLITE3_OPEN_READWRITE);
			if (!$dbhandle) {
				$this->errstr = 'SEVERE: AGI could not connect to (native) SQLite Database "'.
					$this->dbfile.'" - '.$dbhandle->lastErrorMsg;
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
      // dl() is gone in php5, but since this will crash it anyhow, will just leave it as is
			dl('sqlite3.so');
			// That would have crashed PHP if it couldn't load it, so we know it's loaded if
			// it got to here.
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

	$this->debug("Running SQL Command $command", 4);
	// Ensure we're connected to the database.
	if ($this->dbhandle == null) {
		if (!$this->dbhandle = $this->sql_database_connect()) {
			$this->debug('SEVERE: Unable to connect to database.', 1);
			return false;
		}
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
		case "NONE":
			break;
		default:
			$this->errstr = "SEVERE: Uknown Query type '$type' for query '$result'";
			$this->debug($this->errstr, 1);
			return false;
	}

	// Actually do the SQL

	$sqlresult = null;

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
			if ($type == "NONE") {
				return true;
			}
			for ($i = 0; $i <= $this->numrows; $i++) {
				if ($type == "NUM") {
					$sqlresult[$i] = mysql_fetch_array($res, MYSQL_NUM);
				} elseif ($type = "ASSOC") {
					$sqlresult[$i] = mysql_fetch_array($res, MYSQL_ASSOC);
				} else {
					$sqlresult[$i] = mysql_fetch_array($res, MYSQL_BOTH);
				}
			}
			return $sqlresult;
		case "sqlite":
			$res = $this->dbhandle->query($result);
			if (!$res) {
				$this->errstr = "SQLite3 Error: '".$this->dbhandle->lastErrorMsg."' with query '$result'";
				$this->debug($this->errstr, 1);
				return false;
			}
			// Loop through the returned result set, loading it into the array to return
			// to the caller.
			// Return the correct type.
			if ($type == "NONE") {
				return true;
			}
			$i = 0;
			if ($type == "NUM") {
				while ($sqlresult[$i++] = $res->fetchArray(SQLITE3_NUM));
			} elseif ($type = "ASSOC") {
				while ($sqlresult[$i++] = $res->fetchArray(SQLITE3_ASSOC));
			} else {
				while ($sqlresult[$i++] = $res->fetchArray(SQLITE3_BOTH));
			}
			$res->finalize();
			$this->numrows = $i;
			return $sqlresult;
		case "sqlite3":
			// Init the sqlite3 hack variables. 
			global $sql3holderAssoc;
			global $sql3holderNum;
			global $sql3holderRowNbr;

			$sql3holderAssoc = null;
			$sql3holderNum = null;
			$sql3holderRowNbr = 0;

			// If no result is required, just run the query and return the status.
			if ($type == "NONE") {
				$res = sqlite3_exec($this->dbhandle, $result);
				if (!$res) {
					$this->errstr = sqlite3_error($this->dbhandle);
					return false;
				} else {
					$this->errstr = null;
					return $res;
				}
			}
			// This next line uses the sqlite3_hack function, below, to load
			// up the $sql3holder variables.
			$res = sqlite3_exec($this->dbhandle, $result, "sqlite3_hack");
			$this->numrows = $sql3holderRowNbr;
			$this->debug("SQL returned $sql3holderRowNbr Rows", 4);
			if ($sql3holderRowNbr == 0) {
				return true;
			}
			if ($type == "NUM") {
				return $sql3holderNum;
			} elseif ($type == "ASSOC")  {
				return $sql3holderAssoc;
			} else {
				return $sql3holderNum + $sql3holderAssoc;
			}
		default:
			$this->debug("SEVERE: Database type '".$this->db."' NOT SUPPORTED (sql)", 0);
			return false;
	}
  }

  function rename_table($from, $to) {
	switch ($this->db) {
		case "mysql":
		case "sqlite":
		case "sqlite3":
			$this->sql("DROP TABLE `$to`", "NONE", true);
			$sql = "ALTER TABLE `$from` RENAME TO `$to`";
			if(!$res = $this->sql($sql, "NONE", true)) {
				print "Error in sql `$sql` - ".$this->errstr."\n";
				return false;
			} else {
				return true;
			}
		default:
			$this->debug("SEVERE: Database type '".$this->db."' NOT SUPPORTED (rename_table)", 0);
			return false;
	}
  }

  function add_col($tablename, $colname, $type) {
	switch ($this->db) {
		case "mysql":
		case "sqlite":
		case "sqlite3":
			return $this->sql("ALTER TABLE `$tablename` ADD COLUMN `$colname`", "NONE", true);
		default:
			$this->debug("SEVERE: Database type '".$this->db."' NOT SUPPORTED (add_col)", 0);
			return false;
	}
  }

  function drop_col($tablename, $colname) {

	// Ensure we're connected to the database.
	if ($this->dbhandle == null) {
		if (!$this->dbhandle = $this->sql_database_connect()) {
			$this->debug('SEVERE: Unable to connect to database.', 1);
			return false;
		}
	}

	switch ($this->db) {
		case "mysql":
			return $this->sql("ALTER TABLE `$tablename` DROP COLUMN `$colname`");
		case "sqlite":
		case "sqlite3":
		// As SQLite doesn't support much in the way of 'alter table', we need to do some fiddling.
		// We need to rename the table, create a new one without the col that they want deleted,
		// copy everything from the old table, then delete the old table.
		// We use the magic 'sqlite_master' table to get the information about the table.
		// Note we CAN'T use $this->sql (aka, sqlite3_exec) because it segvs when using the
		// sqlite_master table. I don't know enough about sqlite to be able to fix it. This 
		// works though.

			if ($this->db == "sqlite3") {
				$res = sqlite3_query($this->dbhandle, 
					"select `tbl_name`,`sql` from `sqlite_master` where `tbl_name`='$tablename'");
				$sqlarr = sqlite3_fetch_array($res);
				sqlite3_query_close($res);
			} else {
				// We're using the SQLite3 class, which works normally.
				$res = $this->sql("select `tbl_name`,`sql` from `sqlite_master` where `tbl_name`='$tablename'"
					, "ASSOC", true);
				$sqlarr = $res[0];
			}

			$sqlCreate = $sqlarr['sql'];
			// This deletes any line that starts with any number of space characters
			// (^\s+), then has a back tick (`), the name ($colname), another tick
			// (`) and anything else (.+) until the end of the line ($) including the
			// new line character (\n), and it needs to be multiline aware (m)
			$sqlStripped = preg_replace("/^\s+`$colname`.+$\n/m", "", $sqlCreate);
			if ($sqlStripped == $sqlCreate) {
				// Nothing to remove.
				$this->debug("Column $colname doesn't exist in table $tablename", 4);
				return true;
			}

			// Rename table
			$this->rename_table($tablename, "${tablename}_temp");

			// Create new table without $colname
			if (!$this->sql($sqlStripped, "NONE", true)) {
				$this->debug("SQL Command Failed: $sqlStripped\n".$this->errstr."\n");
			}

			// Split the CREATE command into col names and types
			preg_match_all('/\n\s+(`.+`)\s(.+)/', $sqlStripped, $arrNewTableInfo);

			// Join the table names back together, so we can use them in the query
			// below.
			$strAllCols = implode(",", $arrNewTableInfo[1]);

			// Copy everything from the old table to the new
			$sql = "INSERT INTO `$tablename` SELECT $strAllCols FROM ${tablename}_temp";
			if (!$this->sql($sql, "NONE", true)) {
				$this->debug("SQL Command Failed: $sqlStripped\n".$this->errstr."\n");
			}

			// Delete the old table
			$sql = "DROP TABLE ${tablename}_temp";
			if (!$this->sql($sql, "NONE", true)) {
				$this->debug("SQL Command Failed: $sql\n".$this->errstr."\n");
			}
			break;
		default:
			$this->debug("SEVERE: Database type '".$this->db."' NOT SUPPORTED (drop_col)", 0);
			return false;
	}
  }


  function alter_col($tablename, $colname, $type) {

	// Ensure we're connected to the database.
	if ($this->dbhandle == null) {
		if (!$this->dbhandle = $this->sql_database_connect()) {
			$this->debug('SEVERE: Unable to connect to database.', 1);
			return false;
		}
	}

	switch ($this->db) {
		case "mysql":
			return $this->sql("ALTER TABLE `$tablename` CHANGE `$colname` `$colname` $type");
		case "sqlite":
		case "sqlite3":
		// As per remove_col - SQLite doesn't support ALTER TABLE properly. We have to work 
		// around it's limitations.
			if ($this->db == "sqlite3") {
				$res = sqlite3_query($this->dbhandle, 
					"select `tbl_name`,`sql` from `sqlite_master` where `tbl_name`='$tablename'");
				$sqlarr = sqlite3_fetch_array($res);
				sqlite3_query_close($res);
			} else {
				// We're using the SQLite3 class, which works normally.
				$res = $this->sql("select `tbl_name`,`sql` from `sqlite_master` where `tbl_name`='$tablename'"
					, "ASSOC", true);
				$sqlarr = $res[0];
			}

			$sqlCreate = $sqlarr['sql'];

			// Extract the col types for all the cols in the $sqlCreate string
			preg_match_all('/\n\s+`(.+)`\s(.+)[,?$]/', $sqlCreate, $arrNewTableInfo);

			// Which loads the col NAMES into $arr[1] and col TYPES into $arr[2]
			// For ease of use, we'll just make it assocative. 
			$i = 0;
			foreach ($arrNewTableInfo[1] as $name) {
				$arrAssocTypes[$name] = $arrNewTableInfo[2][$i++];
			}

				print_r($arrAssocTypes);
			// and NOW we know what the types are for each name.  Lets make sure
			// that the col you want to change actually exists.
			if (defined($arrAssocTypes[$colname])) {
				$this->debug("SQL Error - Tried to change col $colname, but it doesn't exist", 2);
				print_r($arrAssocTypes);
				return false;
			}

			// Now we need to replace the type of the col in $sqlCreate with the correct one.
			$strOld = "`$colname` ".$arrAssocTypes[$colname];
			$strNew = "`$colname` ".$type;
			$sqlNewCreate = str_replace($strOld, $strNew, $sqlCreate);

			// Right. So we've got the new table definition in $sqlNewCreate, now all we need
			// to do is move the old table out of the way, create the new table, and copy 
			// everything across.
			$this->rename_table($tablename, "${tablename}_temp");
			$this->sql($sqlNewCreate, "NONE", true);

			// Create the list of cols to use on the import.
			$strAllCols = implode(",", $arrNewTableInfo[1]);

			// Copy everything from the old table to the new
			$sql = "INSERT INTO `$tablename` SELECT $strAllCols FROM ${tablename}_temp";
			if (!$this->sql($sql, "NONE", true)) {
				$this->debug("SQL Command Failed: $sql\n".$this->errstr."\n");
			}
			break;
		default:
			$this->debug("SEVERE: Database type '".$this->db."' NOT SUPPORTED (escape)", 0);
			return false;
	}
  }

			
	
  function get_var($value) {
        $r = $this->agi->get_variable( $value );

        if ($r['result'] == 1) {
                $result = $r['data'];
                return $result;
        }
        return '';
  }

  function sql_check($sql) {
	// Anything starting with ALTER is right out. 
	if (preg_match('/^ALTER/', $sql)) {
		$this->debug("SEVERE PROGRAMMING ERROR: Do not use ALTER in SQL Queries. ".
			"Use SQL Class functions. ABORTING.", 0);
		exit;
	}
	// Make sure that at least one pair of backticks has been found.
	if (!preg_match('/\`.+\`/', $sql)) {
		$this->debug("SEVERE PROGRAMMING ERROR: For portability, COLUMNS must be ".
			"surrounded by BACK TICKS (`), yet none were found. Continuing.", 0);
	}
	if (!preg_match('/\'.+\'/', $sql)) {
		$this->debug("SEVERE PROGRAMMING ERROR: For portability, FIELDS must be ".
			"surrounded by SINGLE QUOTES ('), yet none were found. Continuing.", 0);
	}
	return $sql;
  }

  // Escape magic characters that are important to databases
  function escape($str) {

	// Ensure we're connected to the database.
	if ($this->dbhandle == null) {
		if (!$this->dbhandle = $this->sql_database_connect()) {
			$this->debug('SEVERE: Unable to connect to database.', 1);
			return false;
		}
	}

	switch ($this->db) {
		case "mysql":
			return mysql_real_escape_string($str, $this->dbhandle);
		case "sqlite":
		case "sqlite3":
			// SQLite only needs to care about single ticks - "'". Escape
			// that with another tick.
			return str_replace("'", "''", $str);
		default:
			$this->debug("SEVERE: Database type '".$this->db."' NOT SUPPORTED (escape)", 0);
			return false;
	}
  }
	
  function debug($string, $level=3) {
	if (class_exists('AGI')) {
        	$this->agi->verbose($string, $level);
	} else {
		print "$string\n";
	}
  }

		

}

// sqlite3_hack to let us return both an assocative and numeric array.  This
// function gets called by sqlite3_exec, above, and is run once for each row
// returned. There's no 'rewind' of a pointer in the sqlite3_ routines, so
// I had to figure out another way of doing it. This is nasty, but there
// doesn't seem to be another way. 

function sqlite3_hack($data, $column) {
	global $sql3holderRowNbr;
	global $sql3holderNum;
	global $sql3holderAssoc;

	// Don't uncomment this unless you don't care about anything that
	// happens after this - phpagi WILL get confused.
	// print "VERBOSE sqlite3_hack called 4\n";

	$i = 0;
	foreach ($data as $x) {
		$sql3holderNum[$sql3holderRowNbr][] = $column[$i];
		$sql3holderAssoc[$sql3holderRowNbr][$data[$i]] = $column[$i];
		$i++;
	}
	$sql3holderRowNbr++;
	return 0;
}

?>
