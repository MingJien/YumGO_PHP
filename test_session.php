<?php
session_start();
echo "<h3>Session Debug Info</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Cookies: <pre>";
print_r($_COOKIE);
echo "</pre>";
echo "Session Data: <pre>";
print_r($_SESSION);
echo "</pre>";
