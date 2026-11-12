<?php

return function (array $input): array {
    return ['deleted' => delete_trackers_by_name('TC05-Smoke-Tracker')];
};
