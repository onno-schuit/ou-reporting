<?php

require_once("../../config.php");

global $CFG;
require_once("{$CFG->dirroot}/auth/ouidm/auth.php");

if (!$cron_auth = new auth_plugin_ouidm()) echo 'Couldn\'t define cron job.';
if (!$cron_auth->cron_ouidm_course_update()) echo 'Oh oh.. Something went horribly wrong!';
// if (!$cron_auth->cron_ouidm_course_update()) echo 'Oh oh.. Something went horribly wrong!';

?>