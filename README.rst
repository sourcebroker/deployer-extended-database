deployer-extended-database
==========================

.. image:: https://poser.pugx.org/sourcebroker/deployer-extended-database/v/stable
   :target: https://packagist.org/packages/sourcebroker/deployer-extended-database

.. image:: https://img.shields.io/badge/license-MIT-blue.svg?style=flat
   :target: https://packagist.org/packages/sourcebroker/deployer-extended-database

|

.. contents:: :local:

What does it do?
----------------

The package provides additional tasks for deployer (deployer.org) for synchronizing databases between instances.
Most useful are tasks:

1. task "`db:pull`_ [source-instance]" task which allows you to pull database from remote instance to local instance,

2. task "`db:push`_ [target-instance]" task which allows you to push database from local to remote instance,

2. task "`db:copy`_ [source-instance] --options=target:[target-instance]" which allows to copy database between remote instances.

Installation
------------

1) Install package with composer:
   ::

      composer require sourcebroker/deployer-extended-database


2) Put following lines in your deploy.php:
   ::

      require_once(__DIR__ . '/vendor/autoload.php');
      new \SourceBroker\DeployerLoader\Load([
          ['path' => 'vendor/sourcebroker/deployer-instance/deployer'],
          ['path' => 'vendor/sourcebroker/deployer-extended-database/deployer'],
      ]);


4) Create ".env" file in your project root (where you store deploy.php file). The .env file should be out of
   git because you need to store here information about instance name. Additionally put there info about database
   you want to synchronise. You can move the info about database data to other file later but for the tests its better
   to put it in .env file. Remember to protect .env file from downloading with https request (if the root folder
   is readable from WWW server level).
   ::

      INSTANCE="local"

      DATABASE_HOST="127.0.0.1"
      DATABASE_NAME="database_name"
      DATABASE_USER="database_user"
      DATABASE_PASSWORD="password"

   The INSTANCE must correspond to ``host()`` name. You need to put the .env file with proper INSTANCE name and
   database access data on on each of you instances.

5) Define "local" localhost and set the "db_databases" for it. Use following code:
   ::

      (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()

   which will read database data from .env file.
   ::

      localhost('local')
          ->set('deploy_path', getcwd())
          ->set('bin/php', 'php')
          ->set('db_databases', [
              'database_default' => [
                  (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
              ]
          ])

6) Add "db_databases" var for all other hosts. For example for live host it can be:
   ::

      host('live')
          ->setHostname('my-server.example.com')
          ->setRemoteUser('deploy')
          ->set('bin/php', '/usr/bin/php82')
          ->set('deploy_path', '/var/www/myapplication/')
          ->set('db_databases', [
              'database_default' => [
                  (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
              ]
          ])

7) Make sure all instances have the same ``/vendors`` folder with ``deployer-extended-database`` and the same ``deploy.php`` file.
   Most problems are because of differences in ``deploy.php`` file between instances.

8) Run ``dep db:pull live`` to test if all works.

Options
-------

- | **db_databases**
  | *default value:* null
  |
  | Databases to be synchronized. You can define more than one database to be synchronized. See `db_databases`_ for
    options available inside db_databases. Look for `Examples`_ for better understanding of structure.

  |
- | **db_storage_path_relative**
  | *default value:* .dep/database/dumps
  |
  | Path relative to "deploy_path" where you want to store database dumps produced during database synchro commands.

  |
- | **db_export_mysqldump_options_structure**
  | *default value:* --no-data=true --default-character-set=utf8mb4 --no-tablespaces
  |
  | Options `mysqldump` used for exporting the database structure.

  |
- | **db_export_mysqldump_options_data**
  | *default value:* --opt --skip-lock-tables --single-transaction --no-create-db --default-character-set=utf8mb4 --no-tablespaces
  |
  | Options `mysqldump` used for exporting the database data.

  |
- | **db_import_mysql_options_structure**
  | *default value:* --default-character-set=utf8mb4
  |
  | Options `mysql` used for importing the database structure.

  |
- | **db_import_mysql_options_data**
  | *default value:* --default-character-set=utf8mb4
  |
  | Options `mysql` used for importing the database data.

  |
- | **db_import_mysql_options_post_sql_in**
  | *default value:* --default-character-set=utf8mb4
  |
  | Options `mysql` used for importing the additional SQL from ``post_sql_in``.


NOTE: watch that ``utf8mb4`` is forced for all mysql/mysqldump operations.


.. _db\_databases:

Options for "db_databases"
--------------------------

"db_databases" is an array of "database configurations" and "database configuration" is array of configuration parts.
Configuration part can be array or string. If its string then its treated as absolute path to file which should
return array of configuration. Each or array configuration parts is merged. Look for `Examples`_ for better
understanding.

- | **host**
  | *default value:* null
  |
  | Database host.

  |
- | **user**
  | *default value:* null
  |
  | Database user.

  |
- | **password**
  | *default value:* null
  |
  | Database user password.

  |
- | **dbname**
  | *default value:* null
  |
  | Database name.

  |
- | **truncate_tables**
  | *default value:* null
  |
  | Array of tables names that will be truncated with task `db:truncate`_. Usually it should be some caching tables that
    will be truncated while deployment. The value is put between ^ and $ and treated as preg_match. For example
    you can write "cf\_.*" to truncate all tables that starts with "cf\_". The final preg_match checked is /^cf\_.*$/i

  |
- | **ignore_tables_out**
  | *default value:* null
  |
  | Array of tables names that will be ignored while pulling database from target instance with task `db:pull`_
    The value is put between ^ and $ and treated as preg_match. For example you can write "cf\_.*" to truncate all
    tables that starts with "cf\_". The final preg_match checked is /^cf\_.*$/i

  |
- | **post_sql_in**
  | *default value:* null
  |
  | SQL that will be executed after importing database on local instance.

  |
- | **post_sql_in_markers**
  | *default value:* null
  |
  | SQL that will be executed after importing database on local instance. The diffrence over "post_sql_in"
    is that you can use some predefined markers. For now only marker is {{domainsSeparatedByComma}} which consist of all
    domains defined in ``->set('public_urls', ['https://live.example.com']);`` and separated by comma. Having such
    marker allows to change active domain in database after import to other instance as some frameworks keeps domain
    names in database.


Examples
--------

Below examples should illustrate how you should build your database configuration.

Config with one database and database data read from .env file
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

deploy.php file:
::

   set('db_default', [
      'ignore_tables_out' => [
          'caching_.*'
      ]
   ]);

   host('live')
         ->setHostname('my-server.example.com')
         ->setRemoteUser('deploy')
         ->set('bin/php', '/usr/bin/php82')
         ->set('deploy_path', '/var/www/myapplication')
         ->set('db_databases',
            [
              'database_foo' => [
                  get('db_default'),
                  (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
               ],
            ]
         );

   localhost('local')
         ->set('deploy_path', getcwd())
         ->set('bin/php', 'php')
         ->set('db_databases',
            [
              'database_foo' => [
                  get('db_default'),
                  (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
               ],
            ]
         );

Mind that because the db_* settings for all hosts will be the same then you can make the 'db_databases' setting global
and put it out of host configurations. Look for below example where we simplified the config.

deploy.php file:
::

   set('db_databases',
       [
           'database_foo' => [
               'ignore_tables_out' => [
                  'caching_.*'
               ]
               (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
            ],
       ]
   );

   host('live')
       ->setHostname('my-server.example.com')
       ->setRemoteUser('deploy')
       ->set('bin/php', '/usr/bin/php82')
       ->set('deploy_path', '/var/www/myapplication/');

   localhost('local')
      ->set('bin/php', 'php')
      ->set('deploy_path', getcwd());


The .env file should look then like:
::

   INSTANCE="[instance name]"

   DATABASE_HOST="127.0.0.1"
   DATABASE_NAME="database_name"
   DATABASE_USER="database_user"
   DATABASE_PASSWORD="password"

Config with two databases and database data read from .env file
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

deploy.php file:
::

   set('db_databases',
       [
            'database_application1' => [
               'ignore_tables_out' => [
                  'caching_.*'
               ]
            (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig('APP1_')
         ],
            'database_application2' => [
               'ignore_tables_out' => [
                  'cf_.*'
                ]
            (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig('APP2_')
         ],
       ]
   );

   host('live')
       ->setHostname('my-server.example.com')
       ->setRemoteUser('deploy')
       ->set('bin/php', '/usr/bin/php82')
       ->set('deploy_path', '/var/www/myapplication/');

   localhost('local')
      ->set('bin/php', 'php')
      ->set('deploy_path', getcwd());

The .env file should look then like:
::

   INSTANCE="[instance name]"

   APP1_DATABASE_HOST="127.0.0.1"
   APP1_DATABASE_NAME="database_name"
   APP1_DATABASE_USER="database_user"
   APP1_DATABASE_PASSWORD="password"

   APP2_DATABASE_HOST="127.0.0.1"
   APP2_DATABASE_NAME="database_name"
   APP2_DATABASE_USER="database_user"
   APP2_DATABASE_PASSWORD="password"

Config with one database and database config read from from different sources
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

In example we will use:

1) array,
   ::

      'ignore_tables_out' => [
                  'caching_*'
               ]

2) get() which returns array with database options,
   ``get('db_default')``

3) direct file include which returns array with database options
   ``__DIR__ . '/.dep/database/config/additional_db_config.php``

4) class/method which returns array with database options
   ``(new \YourVendor\YourPackage\Driver\MyDriver())->getDatabaseConfig()``

5) closure which returns array with database options
   ``function() { return (new \YourVendor\YourPackage\Driver\MyDriver())->getDatabaseConfig()`` }

Each of this arrays are merged to build final configuration for database synchro.

deploy.php file:
::

   set('db_default', [
      'post_sql_in' => 'UPDATE sys_domains SET hidden=1;'
   ]);

   set('db_databases',
       [
           'database_foo' => [
               [
                   'ignore_tables_out' => [
                      'caching_.*'
                   ]
               ],
               get('db_default'),
               __DIR__ . '/databases/config/additional_db_config.php',
               (new \YourVendor\YourPackage\Driver\MyDriver())->getDatabaseConfig(),
               function() {
                  return (new \YourVendor\YourPackage\Driver\MyDriver())->getDatabaseConfig();
               }
            ],
       ]
   );

   host('live')
       ->setHostname('my-server.example.com')
       ->setRemoteUser('deploy')
       ->set('bin/php', '/usr/bin/php82')
       ->set('deploy_path', '/var/www/myapplication/');

   localhost('local')
      ->set('bin/php', 'php')
      ->set('deploy_path', getcwd());


Config with one database and database config read from "my framework" file
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Its advisable that you create you own special method that will return you framework database data. In below example
its call to ``\YourVendor\YourPackage\Driver\MyDriver()``. This way you do not need to repeat the data of database
in .env file. In that case .env file should hold only INSTANCE.
::

   set('db_databases',
          [
              'database_default' => [
                  (new \YourVendor\YourPackage\Driver\MyDriver())->getDatabaseConfig()
              ],
          ]
      );


Config of truncate_tables, ignore_tables_out, post_sql_in_markers
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Real life example for CMS TYPO3:
::

   set('db_default', [
       'truncate_tables' => [
           'cf_.*'
       ],
       'ignore_tables_out' => [
           'cf_.*',
           'cache_.*',
           'be_sessions',
           'fe_sessions',
           'sys_file_processedfile',
           'tx_devlog',
       ],
   ]);


Tasks
-----

db:backup
+++++++++

Backup database. In background, on target instance, three tasks are executed `db:export`_, `db:compress`_ and `db:dumpclean`_.
Results are stored in ``{{deploy_path}}/.dep/databases/dumps/``.

If ``release`` folder will be detected on ``deploy_path`` (means we are in process of deploy) then it adds two tags to dump name:
``release`` and ``release_[number]`` like in example below:
``2025-03-01_23-36-25#server=local#dbcode=database_default#dumpcode=e8cd33191dffe1642d3e9fb6bf99090f#tags=release+release_91#type=structure.sql.gz``

You can add you own tags with ``--options=tags:tag1+tag2``. Example:
``2025-03-01_23-36-25#server=local#dbcode=database_default#dumpcode=e8cd33191dffe1642d3e9fb6bf99090f#tags=tag1+tag2#type=structure.sql.gz``

**Example**
::

   dep db:backup local
   dep db:backup live
   dep db:backup live --options=dumpcode:mycode
   dep db:backup live --options=dumpcode:mycode,tags:tag1+tag2

db:compress
+++++++++++

Compress dumps with given dumpcode stored in folder ``{{deploy_path}}/.dep/databases/dumps/``"`` on target instance.
There is required option ``--options=dumpcode:[value]`` to be passed.

Look for config vars ``db_compress_suffix``, ``db_compress_command``, ``db_uncompress_command`` for possible ways to overwrite
standard gzip compression with your own.

**Example**
::

   dep db:compress live --options=dumpcode:0772a8d396911951022db5ea385535f6


db:copy
+++++++

This command allows you to copy database between instances.
::

   dep db:copy [source-instance] --options=target:[target-instance]

In the background it runs several other tasks to accomplish this. Lets assume we want to copy database from live
to dev instance. We will run following command on you local instance:

::

   dep db:copy live --options=target:dev

Here are the tasks that will be run in background:

In below description:
   * source instance = live
   * target instance = dev
   * local instance = local

1) First it runs ``dep db:export live --options=dumpcode:123456`` task on source instance. The dumps from export task are stored
   in folder ``{{deploy_path}}/.dep/databases/dumps/`` on target instance.

2) Then it runs ``db:download live --options=dumpcode:123456`` on local instance to download dump files from live instance from
   folder ``{{deploy_path}}/.dep/databases/dumps/`` to local instance to folder ``{{deploy_path}}/.dep/databases/dumps/``. The dump at
   source instance is deleted at this point because already downloaded.

3) Then it runs ``db:process local --options=dumpcode:123456`` on local instance to make some operations directly on SQL dumps files.

4) Then it runs ``db:upload dev --options=dumpcode:123456`` on local instance. This task takes dump files with ``code:123456``
   and send it to dev instance and store it in folder ``{{deploy_path}}/.dep/databases/dumps/``. The dump at
   local instance is deleted at this point because already uploaded to target instance.

5) Then it make backup of target database before importing the dump.

6) Finally it runs ``db:import dev --options=dumpcode:123456`` on target instance. This task reads dumps with ``code:123456`` from folder
   ``{{deploy_path}}/.dep/databases/dumps/`` on dev instance and import it to database.

7) At the very end it runs `db:dumpclean`_ to clean up old dumps on target instance.

Copy to instance defined in ``instance_live_name`` (default ``live``) is special case.
If you copy to highest instance then by default you will be asked twice if you really want to.
You can disable asking by setting ``db_allow_copy_live_force`` to ``true``.
You can also forbid copy to live instance by setting ``db_allow_copy_live`` to ``false``.

db:decompress
+++++++++++++

Decompress dumps with given dumpcode stored in folder ``{{deploy_path}}/.dep/databases/dumps/`` on target instance.
There is required option ``--options=dumpcode:[value]`` to be passed.

Look for config vars ``db_compress_suffix``, ``db_compress_command``, ``db_uncompress_command`` for possible ways to overwrite
standard gzip compression with your own.

**Example**
::

   dep db:decompress live --options=dumpcode:0772a8d396911951022db5ea385535f6

db:download
+++++++++++

Download database dumps with selected dumpcode from folder ``{{deploy_path}}/.dep/databases/dumps/`` on target instance
and store it in folder ``{{deploy_path}}/.dep/databases/dumps/`` on local instance.
There is required option ``--options=dumpcode:[value]`` to be passed.

**Example**
::

   dep db:download live --options=dumpcode:0772a8d396911951022db5ea385535f6

db:dumpclean
++++++++++++

Clean database dump storage on target instance. By default it removes all dumps except last five but you can set your
values and also change the values depending on instance.

**Example**
::

   set('db_dumpclean_keep', 10); // keep last 10 dumps for all instances

   set('db_dumpclean_keep', [
      'live' => 10 // keep last 10 dumps for live instance dumps
      'dev' => 5   // keep last 5 dumps for dev instance dumps
      '*' => 2     // keep last 5 dumps for all other instances dumps
   ]);

   dep db:dumpclean live

db:export
+++++++++

Dump database to folder on local instance located by default in ``{{deploy_path}}/.dep/databases/dumps/``.
Dumps will be stored in two separate files. One with tables structure. The second with data only.
There is required option ``--options=dumpcode:[value]`` to be passed.

**Example**

Example task call:
::

   dep db:export live --options=dumpcode:362d7ca0ff065f489c9b79d0a73720f5

Example output files located in folder {{deploy_path}}/.dep/databases/dumps/:
::

   2025-02-26_14:56:08#server=live#dbcode=database_default#type=data#dumpcode=362d7ca0ff065f489c9b79d0a73720f5.sql
   2025-02-26_14:56:08#server=live#dbcode=database_default#type=structure#dumpcode=362d7ca0ff065f489c9b79d0a73720f5.sql

Example, if you want to export only some databases :
::

   dep db:export live --options=dbCodeFilter:db1+db2

db:import
+++++++++

Import database dump files to target database(s). Files are taken from folder ``{{deploy_path}}/.dep/databases/dumps/``
on target instance. There is required option ``--options=dumpcode:[value]`` to be passed.

**Example**
::

   dep db:import dev --options=dumpcode:0772a8d396911951022db5ea385535f66

Example, if you want to import only some databases :
::

   dep db:import live --options=dbCodeFilter:db1+db2


db:process
++++++++++

This command will run some defined commands on pure sql file as its sometimes needed to remove or replace some strings
directly on sql file before importing. There is required option ``--options=dumpcode:[value]`` to be passed.

**Example**
::

   dep db:process local --options=dumpcode:0772a8d396911951022db5ea385535f66

db:pull
+++++++

This command allows you to pull database from target instance to local instance.
In the background it runs several other tasks to accomplish this.

Pull to instance defined in ``instance_live_name`` (default ``live``) is special case.
If you pull to highest instance then by default you will be asked twice if you really want to.
You can disable asking by setting ``db_allow_pull_live_force`` to ``true``.
You can also forbid pull to live instance by setting ``db_allow_pull_live`` to ``false``.

When option ``--options=fromLocalStorage`` is set the it does not export from remote host but use local files from
``{{deploy_path}}/.dep/databases/dumps/`` folder. Useful to repeat import of database (for example to test upgrade process)
without getting it again and again from remote host.

**Example**
::

   # export from live and import on current host
   dep db:pull live

   # import from database storage of current host
   dep db:pull live --options=fromLocalStorage

   # export and import only some databases
   dep db:pull live --options=dbCodeFilter:db1+db2

db:push
+++++++

This command allows you to push database from local instance to remote instance.
In the background it runs several other tasks to accomplish this.

Here is the list of tasks that will be done when you execute "db:push":

1) First it runs `db:export`_ task on local instance.
2) Then it runs `db:upload`_ on local instance with remote as argument.
3) Then it runs `db:process`_ on remote instance.
4) Then it runs `db:backup`_ on remote instance to make backup before import.
5) Then it runs `db:import`_ on remote instance.

Push to instance defined in ``instance_live_name`` (default ``live``) is special case.
If you push to highest instance then by default you will be asked twice if you really want to.
You can disable asking by setting ``db_allow_push_live_force`` to ``true``.
You can also forbid push to live instance by setting ``db_allow_push_live`` to ``false``.

**Example**
::

   dep db:push live

   # Only push some databases
   dep db:push live --options=dbCodeFilter:db1+db2

db:rmdump
+++++++++

This command will remove all dumps with given dumpcode (compressed and uncompressed).
There is required option ``--options=dumpcode:[value]`` to be passed.

**Example**
::

   dep db:rmdump live --options=dumpcode:0772a8d396911951022db5ea385535f66

db:truncate
+++++++++++

This command allows you to truncate database tables defined in database config var "truncate_tables".
No dumpcode is needed because it operates directly on database.

**Example**
Truncate local instance databases tables.
::

   dep db:truncate

Truncate live instance databases tables.
::

   dep db:truncate live

db:upload
+++++++++

Upload database dumps with selected dumpcode from folder ``{{deploy_path}}/.dep/databases/dumps/`` on local instance and
store it in folder ``{{deploy_path}}/.dep/databases/dumps/`` on target instance.
There is required option ``--options=dumpcode:[value]`` to be passed.

**Example**
::

   dep db:upload live --options=dumpcode:0772a8d396911951022db5ea385535f6


Changelog
---------

See https://github.com/sourcebroker/deployer-extended-database/blob/master/CHANGELOG.rst
