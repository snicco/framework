<?php


    namespace Snicco\Testing;

    use mysqli;

    trait WithDatabaseExceptions
    {

        protected function withDatabaseExceptions()
        {

            global $wpdb;

            /** @var mysqli $mysqli */

            $mysqli = $wpdb->dbh;

            error_reporting(E_ALL);
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

            $mysqli->query("SET SESSION sql_mode='TRADITIONAL'");

        }

    }