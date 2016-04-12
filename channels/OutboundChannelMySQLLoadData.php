<?php
class OutboundChannelMySQLLoadData
{
  private $user;
  private $password;
  private $host;
  private $database;
  private $charset;
  private $table;
  private $truncate;
  private $separator;
  private $enclosure

  private $log = true;

  public function __construct($user, $password, $host, $database, $charset, $table, $truncate, $separator, $enclosure = "\"", $ignoreLines = "0")
  {
    $this->user = $user;
    $this->password = $password;
    $this->host = $host;
    $this->database = $database;
    $this->charset = $charset;
    $this->table = $table;
    $this->truncate = $truncate;
    $this->separator = $separator;
    $this->enclosure = $enclosure;
    $this->ignoreLines = $ignoreLines;
  }

  public function getProposalLocalPath()
  {
    return '.' . $this->user . $this->host . $this->database . $this->table;
  }

  public function putTransfer($localPathTransfer)
  {
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " iniciado.\n";

    $mysqli = new mysqli($this->host, $this->user, $this->password, $this->database);
    if ($mysqli->connect_errno) throw new Exception('No se ha podido conectar: ' . $mysqli->connect_error);
    $mysqli->set_charset($this->charset);

    if(!empty($this->truncate)) {
      $queryResult = $mysqli->query("TRUNCATE TABLE " . $this->table);
      if(!$queryResult) throw new Exception('Error haciendo truncate a la tabla ' . $this->table . ' : ' . $mysqli->errno . ' ' . $mysqli->error);
      if($this->log) echo date('Y-m-d H:i:s') . " Truncate a la tabla " .  $this->table . " hecho.\n";
    }

    foreach(array_diff(scandir($localPathTransfer), array(".", "..")) as $file) {
      $fileLocal = $localPathTransfer . DIRECTORY_SEPARATOR . $file;

      $loadData = "LOAD DATA LOCAL INFILE '" . $fileLocal . "' INTO TABLE " . $this->table
      . " FIELDS TERMINATED BY '" . $this->separator
      . "' OPTIONALLY ENCLOSED BY '" . $this->enclosure
      . "' IGNORE " . $this->ignore_lines . " LINES";
      $queryResult = $mysqli->query($loadData);

      if(!$queryResult) throw new Exception('Error cargando el fichero ' .  basename($fileLocal) . ' a la tabla ' . $this->table . ' : ' . $mysqli->errno . ' ' . $mysqli->error);
      unlink($fileLocal);
      if($this->log) echo date('Y-m-d H:i:s') . " Fichero saliente " .  basename($fileLocal) . " cargado a la tabla " . $this->table . ".\n";
    }
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";
  }
}
?>
