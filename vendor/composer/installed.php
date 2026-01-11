<?php return array(
    'root' => array(
        'name' => 'peanuts/test-plugin',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => '84d9f958d92b5bc3d117e513276d1988d6e50dba',
        'type' => 'project',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'peanuts/test-plugin' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '84d9f958d92b5bc3d117e513276d1988d6e50dba',
            'type' => 'project',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'yahnis-elsts/plugin-update-checker' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '68a70bf887f11351e70804767b0605ba62ce2833',
            'type' => 'library',
            'install_path' => __DIR__ . '/../yahnis-elsts/plugin-update-checker',
            'aliases' => array(
                0 => '9999999-dev',
            ),
            'dev_requirement' => false,
        ),
    ),
);
