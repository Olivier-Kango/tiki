<?php

// A tracker with one text field that registered users can add items to.

return function (array $input): array {
    delete_trackers_by_name('TC05-Smoke-Tracker');
    return create_text_tracker('TC05-Smoke-Tracker', 'Title');
};
