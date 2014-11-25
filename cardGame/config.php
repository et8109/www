<?php

class Config {

  // SQL Server login information.
  const SQL_SERVER = "localhost";
  const SQL_USER = "root";
  const SQL_PASS = "";
  const SQL_DB = "cardGame";

  // Password Hashing algorithm.
  const HASH_ALGO = "sha256";

  // Application Admin User account.
  const ADMIN_USER = "admin";
  const ADMIN_PASS = "";
}

class cards {
    const darkness = 0;
    const light = 1;
}

?>
