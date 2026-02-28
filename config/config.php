<?php


define("BASE_URL", "/laptop-shop/");
define("SITE_NAME", "TechStore");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
