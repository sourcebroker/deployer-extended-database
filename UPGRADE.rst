
Upgrades
--------

1) **17.0.0 -> 18.0.0**

   Change definitions of local host from ``host("local")`` to ``localhost("local")``.
   Add "bin/php" to localhost.

   ::

       OLD

       host('local')
           ->set('deploy_path', getcwd());

       NEW

       localhost('local')
           ->set('deploy_path', getcwd())
           ->set('bin/php', 'php');

2) **Deployer 6 -> 7**

   If you were modifying ``db_databases`` on host level with ``array_merge_recursive`` and in ``db_databases`` there
   were some closures then since Deployer 7 this will no longer work. You can get the same result when using
   ``db_databases_overwrite``.  You can also use ``db_databases_overwrite_global`` to overwrite with similar way on
   global level.

   ::

    OLD

    host('local')
        ->set('deploy_path', getcwd())
        ->set('db_databases', array_merge_recursive(get('db_databases'),
            [
                'database_default' =>
                    [
                        [
                            'post_sql_in' =>
                                '
                                  UPDATE table .....
                                '
                        ]
                    ]
            ]));



    NEW

    host('local')
        ->set('deploy_path', getcwd())
        ->set('db_databases_overwrite',
            [
                'database_default' =>
                    [
                        [
                            'post_sql_in' =>
                                '
                                  UPDATE table .....
                                '
                        ]
                    ]
            ]);

