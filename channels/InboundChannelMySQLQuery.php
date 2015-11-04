<?php
class InboundChannelMySQLQuery
{
  private $user;
  private $password;
  private $host;
  private $database;
  private $query;

  private $localPath;
  private $localPathTransfer;

  private $waitLoop = 5;
  private $log = true;

  public function __construct($user, $password, $host, $database, $query, $filename)
  {
    $this->user = $user;
    $this->password = $password;
    $this->host = $host;
    $this->database = $database;
    $this->query = $query;
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

    $mysqli = new mysqli($this->host, $this->user, $this->password, $this->database);
    if ($mysqli->connect_errno) throw new Exception('No se ha podido conectar: ' . $mysqli->connect_error);

    $queryResult = $mysqli->query($this->query, MYSQLI_USE_RESULT);
    if($queryResult) {
      $localFileTmp = $this->localPath . DIRECTORY_SEPARATOR . "tmp-" . $this->filename;
      $localFile = $this->localPathTransfer . DIRECTORY_SEPARATOR . $this->filename;
      if(!($localFileTmpP = fopen($localFileTmp, 'w'))) throw new Exception('No se ha podido crear el fichero temporal ' . $localFileTmp);
      $i = 0;
      while($queryResultRow = $queryResult->fetch_assoc()) {
        if(!fputcsv($localFileTmpP, ($i == 0 ? array_keys($queryResultRow) : array_values($queryResultRow)), ";")) throw new Exception('Error escribiendo en el fichero temporal ' . $localFileTmp);
        $i++;
      }
      fclose($localFileTmpP);
      if(!rename($localFileTmp, $localFile)) throw new Exception('Error moviendo el fichero temporal a definitivo: ' . $localFile);
      if($this->log) echo date('Y-m-d H:i:s') . " Query volcada al fichero " . $this->filename . "\n";
    }

    $mysqli->close();

    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";

    return $this->localPathTransfer;
  }
}
?>
