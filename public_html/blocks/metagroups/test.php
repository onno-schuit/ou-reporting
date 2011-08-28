<?php
// Preliminaries
require_once( "../../config.php" );
require_once("{$CFG->dirroot}/blocks/metagroups/class.synchronizer.php");
require_once("{$CFG->dirroot}/blocks/moodleblock.class.php");
require_once("{$CFG->dirroot}/blocks/metagroups/block_metagroups.php");

$master_course_id 	= required_param('master_course_id', PARAM_INT);
$block_instance_id 	= required_param('block_instance_id', PARAM_INT);

if (! $course = get_record('course', 'id', $master_course_id)) {
    error("Could not find master course");  
}
require_login($course->id);
$context = get_context_instance(CONTEXT_COURSE, $course->id);

if (! has_capability('block/metagroups:useblock', $context, $USER->id)) {
    error("You are not allowed to perform this operation");  
}
// Actually requested Action
$synchronizer = new synchronizer($block_instance_id);
$synchronizer->synchronize_metacourses_for($master_course_id);
//print_object($synchronizer->associated_metacourses($course->id));
echo "Synchronization completed";
?>
