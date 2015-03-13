<?php
/*
Plugin Name: CasePress SalePoints
Version: 1.2.0
Description: Plugin allows you to display on Google map points stored in the database
Author: Systemo
Author URI: http://systemo.biz/
*/
include_once 'functions/registration.php';                //registration custom post type, taxonomy. change his position and order in dashboard
include_once 'config.php';                                //configuration file
include_once 'functions/shortcode.php';                   //master file that displays the content on the backend
include_once 'functions/retrieving_data.php';             //retrieving data on load and ajax handler. add custom field to post
include_once 'functions/dashboard.php';                   //change output columns in all locations
include_once 'functions/check_post.php';                  //check new post for unique address and lat/lng










