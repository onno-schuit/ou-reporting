<?php  

$block_metagroups_capabilities = array(
    'block/metagroups:useblock' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'user' => CAP_PROHIBIT,
            'guest' => CAP_PROHIBIT,
            'student' => CAP_PROHIBIT,
            'teacher' => CAP_PROHIBIT,
            'editingteacher' => CAP_PROHIBIT,
            'admin' => CAP_ALLOW
        )
    )
);

?>
