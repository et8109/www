<?php

require_once 'config.php';

/**
 * Database interaction class.
 */
class Database {
  const USER_MAX = 20;

  /**
   * Constructs a database interaction object and connects to the DB.
   * install: If true, connects to the DB and performs wipe and fresh
   * install of the DB before constructing the object.
   */
  public function __construct($freshInstall = false) {
    $this->connection = new mysqli(Config::SQL_SERVER,
                                   Config::SQL_USER,
                                   Config::SQL_PASS);

    // SQL connection attempt failed.
    if ($this->connection->connect_error) {
      throw new Exception("Failed to connect to SQL server. Code " 
                                  . $this->connection->connect_error);
    }

    // Perform fresh install before selecting db.
    if ($freshInstall) {
      $this->freshInstall();
    }

    // Try Select DB. If SQL DB selection failed, app is probably not installed.
    if (!$this->connection->select_db(Config::SQL_DB)) {
      throw new Exception("Failed to select database '"
                                  . Config::SQL_DB
                                  . "'. Ensure that you have installed the application.");
    }
  }

  /**
   * Destroys the object when finished and closes the connection.
   */
  public function __destruct() {
    $this->connection->close();
  }

  public function insertUser($user, $pass) {
    if (strlen($user) > Database::USER_MAX) {
      throw new Exception($user, Database::USER_MAX);
    }

    $user = $this->connection->escape_string($user);
    $date = Database::timeStamp();
    $pass = hash(Config::HASH_ALGO, $pass);

    try {
      $this->query("INSERT INTO Users (user, pass, join_date)"
                       . " VALUES ('$user', '$pass', '$date')");
    } catch (Exception $ex) {

      // DUPLICATE ENTRY: A user with the specified name already exists.
      if ($ex->getSqlCode() == 1062) {
        throw new Exception($user);
      } else {
        throw $ex;
      }
    }
  }

  public function authenticateUser($user, $pass) {
    $user = $this->connection->escape_string($user);

    $result = $this->query("SELECT pass FROM Users U WHERE U.user='$user'");
    // Invalid username.
    if ($result->num_rows == 0) {
      throw new Exception("username not found");
    }
    
    $passHash = $result->fetch_row()[0];
    // Invalid password.
    if (hash(Config::HASH_ALGO, $pass) != $passHash) {
      throw new Exception("incorrect password");
    }
    // User authentication successful.
    return;
  }


  public function deleteUser($user) {
    $user = $this->connection->escape_string($user);

    $this->query("DELETE FROM Users WHERE user='$user'");

    return ($this->connection->affected_rows > 0);
  }
  
  /**
   *Returns true if user is in matching
   *False otherwise.
   */
  public function isInMatching($user){
    $user = $this->connection->escape_string($user);

    $c = $this->query("select count(*) from Matching where user='$user'");
    
    return $c->fetch_row()[0] > 0;
  }
  
  /**
   *Adds a player to the matchmaking system
   */
  public function addToMatching($user){
    $user = $this->connection->escape_string($user);

    $this->query("insert into Matching (user,opp) values ('$user','')");
    return;
  }
  
  /**
   *Returns the name of a user in matchmaking and removes the from the queue.
   *Returns false on fail.
   */
  public function findMatch($user){
    $user = $this->connection->escape_string($user);
    //find if an opponent has been assigned
    $opp = $this->query("select opp from Matching where user='$user'");
    $opp = $opp->fetch_row();
    if($opp[0]!=''){
      $this->query("delete from Matching where user='$user'");
      return $opp[0];
    }
    //find a new opponent
    
    $this->query("update Matching set opp='$user' where user!='$user' limit 1");
    if ($this->connection->affected_rows > 0){
      $opp = $this->query("select user from Matching where opp='$user'");
      $opp = $opp->fetch_row();
      $this->query("delete from Matching where user='$user'");
      return $opp[0];
    }
    return false;
  }

  /**
   * Drops the old database and creates the table schemas from scratch.
   */
  private function freshInstall() {

    // Drop the old database *sniffle* goodbye!
    $this->query("DROP DATABASE IF EXISTS " . Config::SQL_DB);

    // Create the parent database.
    $this->query("CREATE DATABASE " . Config::SQL_DB);
    $this->query("USE " . Config::SQL_DB);

    // Create users table.
    $this->query("CREATE TABLE Users ("
                 . "user VARCHAR(25), "
                 . "pass VARCHAR(64) NOT NULL, "
                 . "join_date DATETIME NOT NULL, "
                 . "PRIMARY KEY(user) "
                 . ")");
    
    /*// Create rooms table.
    $this->query("CREATE TABLE Rooms ("
                 . "user VARCHAR(25), "
                 . "rid INT NOT NULL, "
                 . "PRIMARY KEY(user) "
                 . ")");*/
    
    // Create Matching table.
    $this->query("CREATE TABLE Matching ("
                 . "user VARCHAR(25), "
                 . "opp VARCHAR(25), "
                 . "PRIMARY KEY(user) "
                 . ")");
    
    // Create owned table.
    $this->query("CREATE TABLE Owned ("
                 . "user VARCHAR(25), "
                 . "cid INT NOT NULL, "
                 . "PRIMARY KEY(user) "
                 . ")");
    
    // Create cards table.
    $this->query("CREATE TABLE Cards ("
                 . "cid INT, "
                 . "data VARCHAR(25) NOT NULL, "
                 . "PRIMARY KEY(cid) "
                 . ")");

    // Create admin user account.
    $this->insertUser(Config::ADMIN_USER, Config::ADMIN_PASS);

  }

  private function query($query) {
    $result = $this->connection->query($query);
    if (!$result) {
      throw new Exception("Error occurred processing SQL statement '$query': "
                                  . $this->connection->error,
                                  $this->connection->errno);
    }

    return $result;
  }

  /**
   * Gets the current date and time in a SQL ready format.
   */
  private static function timeStamp() {
    return date("Y-m-d H:i:s");
  }
}

?>
