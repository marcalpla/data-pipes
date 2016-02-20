# data-pipes

**Light PHP application** to move data between different types of system through independent processes for each data pipe. A data pipe is **composed of an inbound channel and an outbound channel** and it's executed directly from the shell. You can use tools as for example cron to setup a periodically data pipe execution.

## Execution example

You can see in the launcher_example.sh script a **data pipe example** with a FTP inbound channel and an Amazon S3 outbound channel. The arguments expected for the data pipe execution are the arguments associated to the inbound an outbound channel (mainly the connection details) and a path for the file to be used for the data pipe log output. The DATAPIPE_PATH variable of the launcher_example.sh script is used to define the path to the data_pipe.php application file. This is the core of the application and connects an inbound channel with an outbound channel.

## Channel types

The channel types supported are:

**Inbound:** FTP, SFTP, MySQL Query.
**Outbound:** FTP, Amazon S3.
