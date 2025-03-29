
Changelog
---------

master
------

1) [BUGFIX] Fix using `bin/php` for local context. Deployer `bin/php`, when not a explicitly set as string, is using `which()`
    function, that is using `run()` in background, which is trying to make connection to local ssh. This fix uses `local/bin/php`
    instead which will check locally: first `php_version` setting of host, then if not found it will check `composer.json` for the
    php version. If php version is found the binary will be searched first for X.Y (like 8.4) and then for XY (84).
    Finally if `php_version` or `composer.json` do not give answer about php version it will fallback just to search `php` binary.

2) [BUGFIX] Fix some edge cases for replacing the task names in nested outputs.

3) [BUGFIX] Remove using "stat" as db:download and db:upload commands because not available on some systems. Use filesize()
   from php as command is run locally, use glob() instead of "ls" as for the same reason. Fix the files size calculation.

18.2.0
------

1) [TASK] Extend the dependency to v5 of ``sourcebroker/deployer-loader``.

18.1.1
------
1) [BUGFIX] Last line of ignored tables at db:export task was not shown. This change fix this and also make refactor for this part.

18.1.0
------
1) [FEATURE] Allow to add custom options to ``--options=``. If option has prefix ``tx`` is not validated.
   For example ``--options=txMyOption:myValue``.

18.0.3
------

1) [BUGFIX] The constant GLOB_BRACE is not defined on all systems, like fe Alpine Linux. After this fix GLOB_BRACE
   is no longer used.
2) [BUGFIX] Fix the condition for checking if release_path exists. It was failing when ``db:backup`` at localhost was
   invoked because ``release_path`` is not set then. Replace with usage of ``deploy_path`` and hardcoded ``release``.

18.0.2
------

1) [BUGFIX] Fix wrong condition on ``db:backup``. After this fix tags "release" and "release_X" will be automatically added
   when task ``db:backup`` is run during deploy before ``deploy:symlink``.

18.0.1
------

1) [BUGFIX] PHP 7.4 compatibility fixes.

18.0.0
------

1) [TASK][BREAKING] Change the default charset for mysql/mysqldump operations from utf8 to utf8mb4.
2) [TASK] Add output formatting helper functions in ConsoleUtility.
3) [FEATURE] Add way to validate value of single ``--options`` with preg_match in ConsoleUtility->getOption.
4) [TASK][BREAKING] Add preg_match for dumpcode ``/^[a-zA-Z0-9_]+$/``
5) [TASK] Add support for resolving home in deploy_path
6) [TASK][BREAKING] Remove autocreation of dumpcode on db:export. Since now you need to add your dumpcode
   with --options=dumpcode:mydumpcode
7) [TASK] Move db credentials to temporary file and use it with ``--defaults-file=``. Reason is edge case that ``MYSQL_PWD``
   was overwritten by ``~/.my.cnf`` files. For example: https://github.com/ddev/ddev/pull/6851
8) [FEATURE] Add info about big tables on db:import. Useful to fast check if we import some not needed big tables.
   Default limit is 50MB. You can change it to your value in ``db_import_big_table_size_threshold``.
9) [FEATURE] Add info about ignored tables on mysqldump in db:export. You can configure max_line in
   ``db_export_mysqldump_show_ignore_tables_out_max_line_length``.
10) [TASK][BREAKING] Change bahaviour of ``db:download`` and ``db:upload``. Option ``--remove-source-files`` has been
    removed from rsync and downloaded, uploaded files are not longer automatically removed. Add short info about size
    of downloaded or uploaded file.
11) [TASK] Show ``db:dumpclean`` output only when verbosity higher than regular.
12) [TASK][BREAKING] Change behaviour of ``db:copy``, ``db:pull``, ``db:push``. No longer copy of imported database is
    kept in database storage. This is clean up before implementing a backup of replaced database. Add nice formatting
    of tasks with info about ignored tables, size od downloaded/uploaded database and too big tables.
13) [FEATURE] Add command ``db:import:last`` to import last downloaded database. Bring back keeping last imported databases
    and rotation of those.
14) [FEATURE] Add validation for name of options. If name is wrong task will stop.
15) [TASK][BREAKING] Move normalizeFolder functionality into 'db_storage_path_local' function as it was used only in
    this context. Remove FileUtility->normalizeFolder
16) [TASK][BREAKING] Do not throw error in db:decompress task if dump is already decompressed.
17) [TASK] Add new OptionUtility for managing "--options". Add new option "tags" that allow to set tags for dump filename.
18) [TASK] Refactor db:copy, db:pull, db:push. Store copies of dumps that will be imported and store dump of local database
    before import. This will allow to recover when database overwritten by accident. Add tags to dumps.
19) [TASK][BREAKING] Remove db:import:last task in favour of option for db:pull.
20) [TASK][BREAKING] Remove dependency to symfony/dotenv.

17.0.0
~~~~~~

1) [FEATURE] Add support for SSL connection to database.
2) [FEATURE] Add missing variable for mysql options for post sql import ``db_import_mysql_options_post_sql_in``.

16.1.0
~~~~~~

1) [FEATURE] Add possibility to overwrite ``db_databases`` by setting ``db_databases_global`` and ``db_databases``
   (on host level). See more on UPGRADE file.

16.0.1
~~~~~~

1) [BUGFIX] Fix connectionOptionsString() is already escapeshellarg'ed on RsyncUtility->getSshOptions.

16.0.0
~~~~~~

1) [TASK][BREAKING] Bump dependency to ``sourcebroker/deployer-instance``.
2) [TASK] Code cleanup / update readme.
3) [BUGFIX] Fix wrong function used to output text.


15.0.0
~~~~~~

1) [TASK][BREAKING] Refactor to Deployer 7.
2) [TASK] Extend dependency to internal packages to dev-master.

14.0.0
~~~~~~

1) [TASK][BREAKING] Update dependency to ``sourcebroker/deployer-loader`` which introduce load folder/files
   alphabetically.

13.0.2
~~~~~~

1) [BUGFIX] Use port-parameter in mysqli_connect (tnx to mavolkmer)
2) [TASK] Drop styleci.
3) [TASK] Drop date from licence.

13.0.1
~~~~~~

1) [BUGFIX] Add dependency to sourcebroker/deployer-instance (fix compatibility with symfony/dotenv 5.0)

13.0.0
~~~~~~

1) [TASK] Add ddev config.
2) [TASK][BREAKING] Fix compatibility with symfony/dotenv 5.0 which do not use getenv() by default.

12.2.1
~~~~~~

1) [BUGFIX] Fix changelog typo.

12.2.0
~~~~~~

1) [TASK] Increase `symfony/dotenv` version.

12.1.0
~~~~~~

1) [FEATURE] Use loadEnv function from Symfony\Dotenv if possible.
2) [BUGFIX] Documentation bugfixes.

12.0.0
~~~~~~

1) [TASK][BREAKING] Add new default option for mysqldump '--no-tablespaces' . https://dba.stackexchange.com/questions/271981/access-denied-you-need-at-least-one-of-the-process-privileges-for-this-ope

11.0.2
~~~~~~

1) [BUGFIX] Fix for normalize file regexp.

11.0.1
~~~~~~

1) [BUGFIX] Force dumpcode to be only a-z, A-Z, 0-9, _.

11.0.0
~~~~~~

1) [TASK][BREAKING] Add dependency to deployer-extended-loader.

10.0.1
~~~~~~

1) [BUGFIX] Force dumpcode to be only a-z, A-Z, 0-9.
2) [BUGFIX] Fix for normalize file regexp.

10.0.0
~~~~~~

1) [FEATURE] Add db:push command.
2) [FEATURE] Add FileUtility->locateLocalBinaryPath.
3) [TASK][BREAKING] Remove not needed dependency to deployer-extended-loader.
4) [TASK][BREAKING] Cleanup variables naming.
5) [TASK] Protect copying/pushing/pulling database to top level instance.
6) [TASK] Disable default command for db_process_commands.

9.0.0
~~~~~~

1) [TASK][BREAKING] Compatibility with Deployer 6.4+
2) [TASK][BREAKING] Refactor options to single option --options=key:value,key:value
3) [TASK] Use $host->getSshArguments()->getCliArguments() for creating rsync ssh parameters.

8.0.0
~~~~~

1) [FEATURE] Add option exportTaskAddIgnoreTablesToStructureDump to allow to add ignore tables when exporting structure.
2) [FEATURE] Add option importTaskDoNotDropAllTablesBeforeImport to prevent dropping all tables before import.
3) [TASK] Add vendor and composer.lock to .gitignore.
4) [FEATURE][BREAKING] Implement sourcebroker/deployer-instance for instance management.
5) [BUGFIX] Remove colon from file names because if Windows compatibility.
6) [TASK] Replace RuntimeException with GracefulShutdownException.
7) [TASK] Increase version of sourcebroker/deployer-instance.
8) [TASK] Replace hardcoded instance name with var.
9) [TASK] Normalize use of dots at the end of task description.

7.0.2
~~~~~

1) [BUGFIX] Replace ":" with "=" because Windows compatibility - date separated by ":".

7.0.1
~~~~~

1) [BUGFIX] Replace ":" with "=" because Windows compatibility.

7.0.0
~~~~~

1) [TASK][BREAKING] Possible breaking change for those using global ``dep`` instead of that one in ``./vendor/bin/dep`` as
   ``local/bin/deployer`` is set now to ``./vendor/bin/dep``.

6.2.1
~~~~~

1) [BUGFIX] If publicUrl is with port then this port should be also used for post_sql_in_markers.

6.2.0
~~~~~

1) [FEATURE] Add confirmation for command db:copy (tnx to Michał Jankiewicz)
2) [FEATURE] Add default option to confirmation for command db:copy so it can be used also with -q option for
   unattended.

6.1.2
~~~~~

1) [BUGFIX] Fix $dbDumpCleanKeep calculation in db:dumpclean.

6.1.1
~~~~~

1) [BUGFIX] Move count() out of for so its not calculated each time.

6.1.0
~~~~~

1) [FEATURE] Add ``db:dumpclean`` task. Add ``db:dumpclean`` as last task to ``db:backup`` and ``db:pull``. Add docs.

6.0.0
~~~~~

1) [BREAKING] Remove ``db_deployer_version`` config var as its not needed for deployer/distribution based version now.
2) [DOCS] Change to number ordered list on CHANGELOG.rst.
3) [TASK] Rename ``type`` to ``absolutePath`` in $mysqlDumpArgs of db:export so it have more meaning.
4) [TASK] Improve tasks descriptions.
5) [FEATURE] Add db:compress and db:decompress tasks and extend docs.
6) [TASK] Cleanup for db:upload, db:download tasks.
7) [FEATURE] Compress local dumps after importing them with ``db:pull [instance]``.
8) [FEATURE] Add db:rmdump task and documentation.
9) [FEATURE] Add db:rmdump task at the end of ``db:copy [source] [target]`` task.
10) [FEATURE] Add db:backup task.


5.0.4
~~~~~

1) [BUGFIX] Fix styles ci.


5.0.3
~~~~~

1) [BUGFIX] Do not show error on database pull if ``public_urls`` are not set.

5.0.2
~~~~~

1) [BUGFIX] Remove not needeed exeption throws as the truncate_tables value can be
    not set or return empty value from regexp.

5.0.1
~~~~~

1) [BUGFIX] Add missing dependency to sourcebroker/deployer-loader

5.0.0
~~~~~

1) [TASK] Add dependency to sourcebroker/deployer-loader
2) [TASK][!!!BREAKING] Remove SourceBroker\DeployerExtendedDatabase\Loader.php in favour of using sourcebroker/deployer-loader
3) [TASK][!!!BREAKING] Remove SourceBroker\DeployerExtendedDatabase\Utility\FileUtility->requireFilesFromDirectoryReqursively
   because it was used only in SourceBroker\DeployerExtendedDatabase\Loader.php

4.0.5
~~~~~

1) [BUGFIX] Fix wrongly prepared marker domainsSeparatedByComma when more than one domain

4.0.4
~~~~~

1) [TASK] Make dependency to deployer/deployer-dist.

4.0.3
~~~~~

1) [TASK] Make ``bin/deployer`` use of vendor/bin/dep from deployer-dist.

4.0.2
~~~~~

1) [BUGFIX] Fix rebulding symlink to deployer.phar

4.0.1
~~~~~

1) [BUGFIX] Fix wrong path set for db:copy

4.0.0
~~~~~

1) [TASK] db:export refactor: add possibility to call command on remote instance, add ``db_export_mysqldump_options_structure`` and ``db_export_mysqldump_options_data`` env.
2) [BUGFIX] Fix wrong changlog address in main docs.
3) [TASK] db:truncate refactor add escapeshellargs
4) [TASK] Escapeshellargs for all commands
5) [TASK][BREAKING] Change static utilities method calls to regular objects method call.
6) [TASK] Cleanup ``db:download`` and ``db:upload`` tasks with RsyncUtility
7) [TASK][BREAKING] Rename var ``bin/mysql`` to ``local/bin/mysql``
8) [TASK] Refactor db:import
9) [TASK] db:import refactor add possibility to call command on remote instance
10) [TASK] Enable duplication check for scrutinizer.
11) [TASK] Pass verbosity to commands run locally in db:pull task.
12) [TASK] Move mysql options from db:import task to variables.
13) [TASK] Pass verbosity to commands run locally with use of ConsoleUtility.
14) [TASK] Implement optionRequired() in ConsoleUtility.

3.0.0
~~~~~

1) Set ``default_stage`` as callable. This way ``default_stage`` can be now overwritten in higher level packages.
