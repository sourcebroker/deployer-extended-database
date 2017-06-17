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

1) task "`db:pull`_ [source-instance]" task which allows you to pull database from source instance to current
instance,
2) task "`db:copy`_ [source-instance] [target-instance]" which allows to copy database between instances.

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

4) Create ".env" file in your root with one line.
::

   INSTANCE="local"

The INSTANCE must correspond to server() name. You need to put the .env file with proper INSTANCE name
on each of you instances.

5) Define "local" server and set the "db_databases" for it. Example:
::

   server('local', 'localhost')
       ->set('deploy_path', getcwd())
       ->set('db_databases', [
           'database_default' => [
               [
                   'host' => '127.0.0.1',
                   'dbname' => 'my_database_local',
                   'user' => 'user',
                   'password' => 'password',
               ]
           ]
       ])
       ->set('db_storage_path', getcwd() . '/.deploy/database/dumps')

6) Add "db_databases" var for all other servers. For example for live server it can be:
::

   server('live', 'my-server.example.com')
       ->user('deploy')
       ->set('db_databases', [
           'database_default' => [
               [
                   'host' => '127.0.0.1',
                   'dbname' => 'my_database_live',
                   'user' => 'user',
                   'password' => 'password',
               ]
           ]
       ])
       ->set('deploy_path', '/var/www/myapplication/')
       ->set('db_storage_path', '/var/www/myapplication/shared/database/dumps')


7) Make sure all instances have /vendors folder with deployer-extended-database and deploy.php file.

Options
-------

You can set following options:

bin/mysqldump
bin/mysql
local/bin/deployer
bin/deployer


Options for "db_databases"
--------------------------

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
  | Tables that will be ignored while pulling database from target instance with task `db:pull`_ Table name is put
    between ^ and $ and treated as preg_match. For example you can write "cf_.*" to ignore all tables that starts
    with "cf_". The final preg_match checked is "/^cf_.*$/i"

  |
- | **post_sql_in**
  | *default value:* null
  |
  | SQL that will be executed after importing database on current instance.

|

Config is stored in var "db_databases" which is an array of "database configurations".
"database configuration" is array of configuration parts. Configuration part can be array or string.
If its string then its treated as absolute path to file which should return array of configuration.
Each or array configuration parts is merged.

Below example should illustrate above:

::

   set('db_defaults', [
      'ignore_tables_out' => [
          'cf_*'
      ]

   ]);

   set(
          'db_databases',
          [
              'database_foo' => [
                  [
                      'host' => '127.0.0.1',
                      'user' => 'foo',
                      'password' => 'foopass',
                      'dbname' => 'foo',
                  ],
                  get('db_defaults')
              ],
              'database_bar' => [
                  get('db_defaults'),
                  __DIR__ . '/.database/config-out-of-git/database_bar.php'
              ],
          ]
      );

Its advisable that you create a special method that will return you framework database data. So example
configuration can look then like:

::

   set(
          'db_databases',
          [
              'database_default' => [
                  get('db_default'),
                  (new \MyVendor\MyClass\MySystem())->getDatabaseConfig()
              ],
          ]
      );


Another example for CMS TYPO3:
::

   set('db_default', [
       'truncate_tables' => [
           'cf_.*'
       ],
       'ignore_tables_out' => [
           'cf_.*',
           'cache_.*',
           'be_sessions',
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
       'post_sql_in' => ''
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
