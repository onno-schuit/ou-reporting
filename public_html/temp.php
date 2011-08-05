<?php
mysql_connect("localhost", "root", "paarse") or
    die("Could not connect: " . mysql_error());
mysql_select_db("ou") or die("Could not select db");

$result = mysql_query("SELECT * FROM mdl_log");

echo "starting output<br/>";

if (!$file = fopen("/home/onno/php/ou/public_html/temp.csv", a)) exit("Could not open or create file");
while ($row = mysql_fetch_array($result)) {
    fwrite($file, $row['time'] . "," . $row['userid'] . "," . $row['module'] ."\n");
}
echo "end";

fclose($file);
mysql_free_result($result);
?>
