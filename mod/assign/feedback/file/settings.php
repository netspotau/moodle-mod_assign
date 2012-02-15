<?php

    if (isset($CFG->maxbytes)) {
        $settings->add(new admin_setting_configselect('feedback_file_maxbytes', get_string('maximumsize', 'feedback_file'),
                          get_string('configmaxbytes', 'feedback_file'), 1048576, get_max_upload_sizes($CFG->maxbytes)));
    }
