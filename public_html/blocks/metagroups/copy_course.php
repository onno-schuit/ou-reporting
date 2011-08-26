<?php
// Preliminaries
require_once( "../../config.php" );
require_once("{$CFG->dirroot}/blocks/metagroups/class.metagroups.php");
require_once("{$CFG->dirroot}/blocks/moodleblock.class.php");
require_once("{$CFG->dirroot}/blocks/metagroups/block_metagroups.php");

$source_course_id 	= required_param('source_course_id', PARAM_INT);
$target_course_id 	= required_param('target_course_id', PARAM_INT);
$block_instance_id 	= required_param('block_instance_id', PARAM_INT);


if (! $course = get_record('course', 'id', $target_course_id)) {
    error("Could not find target course");  
}
require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

if (! has_capability('block/metagroups:useblock', $context, $USER->id)) {
    error("You are not allowed to perform this operation");  
}

// Actually requested Action
$synchronizer = new metagroups();
if (!$synchronizer->copy_course($source_course_id, $target_course_id)) {
    error("Something went wrong while copying users and groups from the source course ($source_course_id) to the target course ($target_course_id)"); 
}

block_metagroups::store_template_course_id($target_course_id, $source_course_id, $block_instance_id);
redirect("$CFG->wwwroot/course/view.php?id=$target_course_id", get_string('message_target_course_synchronized', 'block_metagroups'), $delay=2);


?>
