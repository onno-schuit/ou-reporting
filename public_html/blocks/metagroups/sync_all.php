<?php
// Preliminaries
require_once( "../../config.php" );
require_once("{$CFG->dirroot}/blocks/moodleblock.class.php");
require_once("{$CFG->dirroot}/blocks/metagroups/block_metagroups.php");


// Actually requested Action
$synchronizer = new synchronizer();
$synchronizer->synchronize_all();
echo "Synchronization completed";
?>
