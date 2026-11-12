<?php

// Creates a wiki page that calls the HelloWorld sample plugin from
// _custom/shared/wiki-plugins/. Runs through lib/test/e2e/fixtures/bootstrap.php.

return function (array $input): array {
    $pageName = 'E2E-Custom-HelloWorld';
    $tikilib = TikiLib::lib('tiki');

    if ($tikilib->page_exists($pageName)) {
        $tikilib->remove_all_versions($pageName);
    }

    $tikilib->create_page($pageName, 0, '{HELLOWORLD()}e2e{HELLOWORLD}', $tikilib->now, 'Created by the _custom e2e example', 'admin', '127.0.0.1');

    return ['pageName' => $pageName];
};
