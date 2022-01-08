
Changelog
---------

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
