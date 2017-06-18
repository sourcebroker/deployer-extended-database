deployer-extended-database
==========================

.. image:: https://styleci.io/repos/94528993/shield?branch=master
   :target: https://styleci.io/repos/94528993

.. image:: https://scrutinizer-ci.com/g/sourcebroker/deployer-extended-database/badges/quality-score.png?b=master
   :target: https://scrutinizer-ci.com/g/sourcebroker/deployer-extended-database/?branch=master

.. image:: http://img.shields.io/packagist/v/sourcebroker/deployer-extended-database.svg?style=flat
   :target: https://packagist.org/packages/sourcebroker/deployer-extended-database

.. image:: https://img.shields.io/badge/license-MIT-blue.svg?style=flat
   :target: https://packagist.org/packages/sourcebroker/deployer-extended-database

|

.. contents:: :local:

What does it do?
----------------

The package provides additional tasks for deployer (deployer.org) for synchronising databases between instances.
Most useful are two tasks:

1. task "`db:pull`_ [source-instance]" task which allows you to pull database from source instance to current
   instance,

2. task "`db:copy`_ [source-instance] [target-instance]" which allows to copy database between instances.

Rest of task are subtasks of db:pull or db:copy

Installation
------------

1) Install package with composer:

   ::

   composer require sourcebroker/deployer-extended-database


2) If you are using deployer as composer package then just put following line in your deploy.php:

   ::

   new \SourceBroker\DeployerExtendedDatabase\Loader();


3) If you are using deployer as phar then put following lines in your deploy.php:
   ::

   require __DIR__ . '/vendor/autoload.php';
   new \SourceBroker\DeployerExtendedDatabase\Loader();


4) Create ".env" file in your project root (where you store deploy.php file). The .env file should be out of
   git because you need to store here information about instance name. Additionally put there info about database
   you want to synchronise. You can move the info about database data to other later but for the tests its better
   to put it in .env file. Remember to protect .env file from downloading with https request.
   ::

   INSTANCE="local"

   DATABASE_HOST="127.0.0.1"
   DATABASE_NAME="database_name"
   DATABASE_USER="database_user"
   DATABASE_PASSWORD="password"

   The INSTANCE must correspond to server() name. You need to put the .env file with proper INSTANCE name and
   database access data on on each of you instances.

5) Define "local" server and set the "db_databases" for it. Use
   ``(new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()``:
   which will read database data from .env file.
   ::

   server('local', 'localhost')
       ->set('deploy_path', getcwd())
       ->set('db_databases', [
           'database_default' => [
               (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
           ]
       ])

6) Add "db_databases" var for all other servers. For example for live server it can be:
   ::

   server('live', 'my-server.example.com')
       ->user('deploy')
       ->set('deploy_path', '/var/www/myapplication/')
       ->set('db_databases', [
           'database_default' => [
               (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
           ]
       ])


7) Make sure all instances have the same /vendors folder with deployer-extended-database and the same deploy.php file.
   Most problems are because of differences in deploy.php file between instances.

8) Run ``dep db:pull live`` to test if all works.

Options
-------

- | **db_databases**
  | *default value:* null
  |
  | Databases to be synchronized. You can define more than one database to be synchronized. See `db_databases`_ for
    options available inside db_databases. Look for `Examples`_ for better understanding of structure.

- | **db_storage_path_relative**
  | *default value:* .dep/database/dumps
  |
  | Path relative to "deploy_path" where you want to store database dumps produced during database synchro commands.


.. _db_databases:
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
    will be truncated while deployment. Table name is put between ^ and $ and treated as preg_match. For example
    you can write "cf_.*" to truncate all tables that starts with "cf_". The final preg_match checked is /^cf_.*$/i

  |
- | **ignore_tables_out**
  | *default value:* null
  |
  | Array of tables names that will be ignored while pulling database from target instance with task `db:pull`_
    Table name is put between ^ and $ and treated as preg_match. For example you can write "cf_.*" to ignore all
    tables that starts with "cf_". The final preg_match checked is "/^cf_.*$/i"

  |
- | **post_sql_in**
  | *default value:* null
  |
  | SQL that will be executed after importing database on current instance.

  |
- | **post_sql_in_markers**
  | *default value:* null
  |
  | SQL that will be executed after importing database on current instance. The diffrence over "post_sql_in"
    is that you can use some predefined markers. For now only marker is {{domainsSeparatedByComma}} which consist of all
    domains defined in ``->set('public_urls', ['https://live.example.com']);`` and separated by comma. Having such
    marker allows to change active domain in database after import to other instnace as some frameworks keeps domain
    names in database.


|


Examples
--------

Below examples should illustrate how you should build your database configuration.

Config with one database and database data read from .env file
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Deploy.php file:
::

   set('db_defaults', [
      'ignore_tables_out' => [
          'caching_*'
      ]
   ]);

   server('live', 'my-server.example.com')
         ->user('deploy')
         ->set('deploy_path', '/var/www/myapplication/')
         ->set('db_databases',
            [
              'database_foo' => [
                  get('db_defaults'),
                  (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
               ],
            ]
         );

   server('local', 'localhost')
         ->set('deploy_path', getcwd())
         ->set('db_databases',
            [
              'database_foo' => [
                  get('db_defaults'),
                  (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
               ],
            ]
         );

Mind that because the db_* settings for all server will be the same then you can make the 'db_databases' setting global
and put it out of server configurations. Look for below example where we simplified the config.

Deploy.php file:
::

   set('db_databases',
       [
           'database_foo' => [
               'ignore_tables_out' => [
                  'caching_*'
               ]
               (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig()
            ],
       ]
   );

   server('live', 'my-server.example.com')
       ->user('deploy')
       ->set('deploy_path', '/var/www/myapplication/');

   server('local', 'localhost')
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

Deploy.php file:
::

   set('db_databases',
       [
            'database_application1' => [
               'ignore_tables_out' => [
                  'caching_*'
               ]
            (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig('APP1_')
         ],
            'database_application2' => [
               'ignore_tables_out' => [
                  'cf_*'
                ]
            (new \SourceBroker\DeployerExtendedDatabase\Driver\EnvDriver())->getDatabaseConfig('APP2_')
         ],
       ]
   );

   server('live', 'my-server.example.com')
       ->user('deploy')
       ->set('deploy_path', '/var/www/myapplication/');

   server('local', 'localhost')
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

In examples we will use:
1) array,
2) get() which returns array,
3) direct file include (which should return array)
4) class/method (which should return array)

Each of this arrays are merged to build final configuration for database synchro.

Deploy.php file:
::

   set('db_default', [
      'post_sql_in' => 'UPDATE sys_domains SET hidden=1;'
   ]);

   set('db_databases',
       [
           'database_foo' => [
               'ignore_tables_out' => [
                  'caching_*'
               ]
               get('db_default'),
               __DIR__ . '/databases/conifg/additional_db_config.php
               (new \YourVendor\YourPackage\Driver\MyDriver())->getDatabaseConfig(),
            ],
       ]
   );

   server('live', 'my-server.example.com')
       ->user('deploy')
       ->set('deploy_path', '/var/www/myapplication/');

   server('local', 'localhost')
      ->set('deploy_path', getcwd());


Config with one database and database config read from "my framework" file
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Its advisable that you create you own special method that will return you framework database data. In below example
its call to ``\YourVendor\YourPackage\Driver\MyDriver()``. This way you do not need to repeat the data of database
in .env file.

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
           'sys_history',
           'sys_file_processedfile',
           'sys_log',
           'sys_refindex',
           'tx_devlog',
           'tx_extensionmanager_domain_model_extension',
           'tx_realurl_chashcache',
           'tx_realurl_errorlog',
           'tx_realurl_pathcache',
           'tx_realurl_uniqalias',
           'tx_realurl_urldecodecache',
           'tx_realurl_urlencodecache',
           'tx_powermail_domain_model_mails',
           'tx_powermail_domain_model_answers',
           'tx_solr_.*',
           'tx_crawler_queue',
           'tx_crawler_process',
       ],
       'post_sql_in_markers' => 'UPDATE sys_domain SET hidden = 1;
                                 UPDATE sys_domain SET sorting = sorting + 100;
                                 UPDATE sys_domain SET sorting = 1, hidden = 0 WHERE domainName IN ({{domainsSeparatedByComma}});
                                '
   ]);


Tasks
-----

db:copy
+++++++

This command allows you to copy database between instances.
In the background it runs several other tasks to accomplish this.

Here is the list of tasks that will be done afer "db:move":

1) First it runs `db:export`_ task on target instance and get the "dumpcode" as return to use it in next commands.
2) Then it runs `db:download`_ on current instance (with "dumpcode" value from first task).
3) Then it runs `db:process`_ on current instance (with "dumpcode" value from first task).
4) Then it runs `db:upload`_ on current instance (with "dumpcode" value from first task).
5) Then it runs `db:import`_ on target instance (with "dumpcode" value from first task).


**Example**

Example call when you are on your local instance can be:
::

   dep db:move live dev

If you would be logged to ssh of dev instance then you could just use "dep db:pull live".


db:download
+++++++++++

Download database from target instance to current instance.
There is required option --dumpcode to be passed.

**Example**
::

   dep db:download live --dumpcode=0772a8d396911951022db5ea385535f6

db:export
+++++++++

Export database to database storage folder on current instance. The database will be stored in two separate files.
One with tables structure. The second with data only. There is option --dumpcode that can be passed. If there is
no --dumpcode option then its created and returned as json structure.

**Example**

Example task call:
::

   dep db:export

Example output files:
::

   2017-02-26_14:56:08#server:live#dbcode:database_default#type:data#dumpcode:362d7ca0ff065f489c9b79d0a73720f5.sql
   2017-02-26_14:56:08#server:live#dbcode:database_default#type:structure#dumpcode:362d7ca0ff065f489c9b79d0a73720f5.sql


Example task call with dumpcode:
::

   dep db:export --dumpcode=123456

Example output files:
::

   2017-02-26_14:56:08#server:live#dbcode:database_default#type:data#dumpcode:123456.sql
   2017-02-26_14:56:08#server:live#dbcode:database_default#type:structure#dumpcode:123456.sql

db:import
+++++++++

Import database from current instance database storage. There is required option --dumpcode to be passed.

**Example**
::

   dep db:import --dumpcode=0772a8d396911951022db5ea385535f66

db:process
++++++++++

This command will run some defined commands on pure sql file as its sometimes needed to remove or replace some strings
directly on sql file before importing. There is required option --dumpcode to be passed.

**Example**
::

   dep db:process --dumpcode=0772a8d396911951022db5ea385535f66

db:pull
+++++++

This command allows you to pull database from target instance to current instance.
In the background it runs several other tasks to accomplish this.

Here is the list of tasks that will be done afer "db:pull":

1) First it runs `db:export`_ task on target instance and get the "dumpcode" as return to use it in next commands.
2) Then it runs `db:download`_ on current instance (with "dumpcode" value from first task).
3) Then it runs `db:process`_ on current instance (with "dumpcode" value from first task).
4) Then it runs `db:import`_ on current instance (with "dumpcode" value from first task).

**Example**
::

   dep db:pull live

db:truncate
+++++++++++

This command allows you to truncate database tables defined in database config var "truncate_tables"

**Example**
::

   dep db:truncate --dumpcode=0772a8d396911951022db5ea385535f6

db:upload
+++++++++

This command uploads the sql dump file from current instance database storage to target instance
database storage. There is required option --dumpcode to be passed.

**Example**

Take database with dumpcode 0772a8d396911951022db5ea385535f6 from current instance and upload it to
database storage folder on live instance.
::

   dep db:upload live --dumpcode=0772a8d396911951022db5ea385535f6
