
Changelog
---------

3.1.0
~~~~~

a) [TASK] db:export refactor:
   - add possibility to call command on remote instance
   - add "db_export_mysqldump_options_structure" and "db_export_mysqldump_options_data" env

b) [BUGFIX] Fix wrong changlog address in main docs.
c) [TASK] db:truncate refactor:
   - add escapeshellargs

d)

3.0.0
~~~~~

a) Set "default_stage" as callable. This way "default_stage" can be now overwritten in higher level packages.
