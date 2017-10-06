
Changelog
---------

5.0.2
~~~~~

a) [BUGFIX] Remove not needeed exeption throws as the truncate_tables value can be
    not set or return empty value from regexp.


5.0.1
~~~~~

a) [BUGFIX] Add missing dependency to sourcebroker/deployer-loader

5.0.0
~~~~~

a) [TASK] Add dependency to sourcebroker/deployer-loader
b) [TASK][!!!BREAKING] Remove SourceBroker\DeployerExtendedDatabase\Loader.php in favour of using sourcebroker/deployer-loader
c) [TASK][!!!BREAKING] Remove SourceBroker\DeployerExtendedDatabase\Utility\FileUtility->requireFilesFromDirectoryReqursively
   because it was used only in SourceBroker\DeployerExtendedDatabase\Loader.php

4.0.5
~~~~~

1) [BUGFIX] Fix wrongly prepared marker domainsSeparatedByComma when more than one domain

4.0.4
~~~~~

1) [TASK] Make dependency to deployer/deployer-dist.

4.0.3
~~~~~

1) [TASK] Make "bin/deployer" use of vendor/bin/dep from deployer-dist.

4.0.2
~~~~~

1) [BUGFIX] Fix rebulding symlink to deployer.phar

4.0.1
~~~~~

1) [BUGFIX] Fix wrong path set for db:copy

4.0.0
~~~~~

1) [TASK] db:export refactor:
   - add possibility to call command on remote instance
   - add "db_export_mysqldump_options_structure" and "db_export_mysqldump_options_data" env
2) [BUGFIX] Fix wrong changlog address in main docs.
3) [TASK] db:truncate refactor:
   - add escapeshellargs
4) [TASK] Escapeshellargs for all commands
5) [TASK][BREAKING] Change static utilities method calls to regular objects method call.
6) [TASK] Cleanup db:download and db:upload tasks with RsyncUtility
8) [TASK][BREAKING] Rename var "bin/mysql" to "local/bin/mysql"
9) [TASK] Refactor db:import
10) [TASK] db:import refactor:
   - add possibility to call command on remote instance
11) [TASK] Enable duplication check for scrutinizer.
12) [TASK] Pass verbosity to commands run locally in db:pull task.
13) [TASK] Move mysql options from db:import task to variables.
14) [TASK] Pass verbosity to commands run locally with use of ConsoleUtility.
15) [TASK] Implement optionRequired() in ConsoleUtility.

3.0.0
~~~~~

1) Set "default_stage" as callable. This way "default_stage" can be now overwritten in higher level packages.
