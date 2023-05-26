# Migrating MySQL 5.7 to 8.0 on AWS RDS

Just completed the research on an upcoming migration of AWS RDS database from Aurora MySQL 5.7-compatible to latest 8.0-compatible. All went well but there may be some issues when using old versions of PHP with old `mysqli` extensions.

Problem was at connection, the following error was returned:

 `Connect Error (2054) Server sent charset unknown to the client. Please, report to the developers` 
 
 Some research gave me that MySQL 8.0 servers by default use `utf8mb4` charset, causing issues for older versions of `mysqli`. 

 The solution is either to upgrade the client or configure the server to use `utf8` charset instead. With AWS RDS this is managed by setting the following cluster parameters:

 ```
"character_set_client": "utf8",
"character_set_connection": "utf8",
"character_set_database": "utf8",
"character_set_server": "utf8"
```

Following the AWS recommendations to always create a separate parameter group, this is easy to add. A reboot of the server is required so the parameters are applied.
