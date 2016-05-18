<?php
class InboundChannelPostgreSQLQuery
{
  private $user;
  private $password;
  private $host;
  private $port;
  private $database;
  private $charset;
  private $query;
  private $queryBatchRows;
  private $filename;

  private $localPath;
  private $localPathTransfer;

  private $log = true;

  public function __construct($user, $password, $host, $port, $database, $charset, $query, $queryBatchRows, $filename)
  {
    $this->user = $user;
    $this->password = $password;
    $this->host = $host;
    $this->port = $port;
    $this->database = $database;
    $this->charset = $charset;
    $this->query = $query;
    $this->queryBatchRows = $queryBatchRows;
    $this->filename = $filename;
  }

  private function setLocalPath($localPath)
  {
    $this->localPath = $localPath;
    $this->localPathTransfer = $this->localPath . DIRECTORY_SEPARATOR . 'transfer';
  }

  public function getProposalLocalPath()
  {
    return '.' . $this->user . $this->host . $this->database;
  }

  public function getTransfer($localPath)
  {
    $this->setLocalPath($localPath);

    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " iniciado.\n";

    if(!file_exists($this->localPath)) {
      if(!mkdir($this->localPath, 0700)) throw new Exception('No se ha podido crear el directorio local auxiliar ' . $this->localPath);
    }
    if(!file_exists($this->localPathTransfer)) {
      if(!mkdir($this->localPathTransfer, 0700)) throw new Exception('No se ha podido crear el directorio local auxiliar para transferencias ' . $this->localPathTransfer);
    }

    $localFileTmp = $this->localPath . DIRECTORY_SEPARATOR . "tmp-" . $this->filename;
    $localFile = $this->localPathTransfer . DIRECTORY_SEPARATOR . $this->filename;

    $psqlconn = pg_connect("host=" . $this->host . " port=" . $this->port . " dbname=" . $this->database . " user=" . $this->user . " password=" . $this->password . " options='--client_encoding=" . $this->charset . "'");
    if (!$psqlconn) throw new Exception('No se ha podido conectar: ' . pg_last_error());

    if($this->queryBatchRows > 0) {
      $queryCount = pg_query("select count(*) from (" . $this->query . ")");
      if (!$queryCount) throw new Exception('No se ha podido hacer la consulta: ' . pg_last_error());
      $queryCount = pg_fetch_row($queryCount);
      $queryCount = $queryCount[0];

      $offset = 0;

      while($offset < $queryCount) {
        $this->dumpQuery(
          "select * from (" . $this->query . ") offset " . $offset . " limit " . $this->queryBatchRows,
          $offset == 0,
          $localFileTmp,
          ($offset == 0 ? 'wb' : 'ab')
        );
        $offset += $this->queryBatchRows;
      }
    } else {
      $this->dumpQuery(
        $this->query,
        true,
        $localFileTmp,
        'wb'
      );
    }

    if(!rename($localFileTmp, $localFile)) throw new Exception('Error moviendo el fichero temporal a definitivo: ' . $localFile);
    if($this->log) echo date('Y-m-d H:i:s') . " Query volcada al fichero " . $this->filename . "\n";
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";

    return $this->localPathTransfer;
  }

  private function dumpQuery($query, $header, $filename, $filemode) {
    $queryResult = pg_query($query);
    if (!$queryResult) throw new Exception('No se ha podido hacer la consulta: ' . pg_last_error());
    if(!($fileP = fopen($filename, $filemode))) throw new Exception('No se ha podido abrir o crear el fichero temporal ' . $filename);
    if($header) {
      $queryResultFieldsName = array();
      $i = 0;
      while($i < pg_num_fields($queryResult)) {
        $queryResultFieldsName[] = pg_field_name($queryResult, $i);
        $i++;
      }
    }
    $i = 0;
    do {
      if($i != 0 || $header) {
        if(!fputcsv($fileP, ($header && $i == 0 ? $queryResultFieldsName : $queryResultRow), ";")) throw new Exception('Error escribiendo en el fichero temporal ' . $localFileTmp);
      }
      $i++;
    } while($queryResultRow = str_replace(array("\r","\n","\"","'"), " ", pg_fetch_row($queryResult)));
    fclose($fileP);
  }
}
?>
