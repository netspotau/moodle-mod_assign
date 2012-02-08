<?php

    if (isset($CFG->maxbytes)) {
        $settings->add(new admin_setting_configselect('submission_file_maxbytes', get_string('maximumsubmissionsize', 'submission_file'),
                          get_string('configmaxbytes', 'submission_file'), 1048576, get_max_upload_sizes($CFG->maxbytes)));
    }
