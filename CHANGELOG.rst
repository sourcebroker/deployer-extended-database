
Changelog
---------

4.0.0
~~~~~

a) [TASK] db:export refactor:
   - add possibility to call command on remote instance
   - add "db_export_mysqldump_options_structure" and "db_export_mysqldump_options_data" env
b) [BUGFIX] Fix wrong changlog address in main docs.
c) [TASK] db:truncate refactor:
   - add escapeshellargs
d) [TASK] Escapeshellargs fro all commands
e) [TASK/BREAKING] Change static utilities method calls to regular objects method call.
f) [TASK] Cleanup db:download and db:upload tasks with RsyncUtility
g) [TASK/BREAKING] Rename var "bin/mysql" to "local/bin/mysql"
h) [TASK] Refactor db:import
i) [TASK] db:import refactor:
   - add possibility to call command on remote instance
j) [TASK] Enable duplication check for scrutinizer.
k) [TASK] Pass verbosity to commands run locally in db:pull task.


3.0.0
~~~~~

a) Set "default_stage" as callable. This way "default_stage" can be now overwritten in higher level packages.
