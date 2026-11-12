<?php

// Removes the page tc01_setup.php created.

return function (array $input): array {
    $pageName = $input['pageName'] ?? 'E2E-Custom-HelloWorld';
    $tikilib = TikiLib::lib('tiki');

    if ($tikilib->page_exists($pageName)) {
        $tikilib->remove_all_versions($pageName);
    }

    return ['removed' => $pageName];
};
