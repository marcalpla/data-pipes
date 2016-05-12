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
  private $replace;
  private $separator;
  private $ignoreLines;
  private $enclosure;

  private $log = true;

  public function __construct($user, $password, $host, $database, $charset, $table, $truncate, $replace, $separator, $ignoreLines, $enclosure = "\"")
  {
    $this->user = $user;
    $this->password = $password;
    $this->host = $host;
    $this->database = $database;
    $this->charset = $charset;
    $this->table = $table;
    $this->truncate = $truncate;
    $this->replace = $replace;
    $this->separator = $separator;
    $this->ignoreLines = $ignoreLines;
    $this->enclosure = $enclosure;
  }

  public function getProposalLocalPath()
  {
    return '.' . $this->user . $this->host . $this->database . $this->table;
  }

  public function putTransfer($localPathTransfer)
  {
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " iniciado.\n";

    $files = array_diff(scandir($localPathTransfer), array(".", ".."));

    if(!empty($files)) {
      $mysqli = new mysqli();
      $mysqli->init();
      $mysqli->options(MYSQLI_OPT_LOCAL_INFILE, true);
      $mysqli->real_connect($this->host, $this->user, $this->password, $this->database);
      if($mysqli->connect_errno) throw new Exception('No se ha podido conectar: ' . $mysqli->connect_error);
      $mysqli->set_charset($this->charset);

      if(!empty($this->truncate)) {
        $queryResult = $mysqli->query("TRUNCATE TABLE " . $this->table);
        if(!$queryResult) throw new Exception('Error haciendo truncate a la tabla ' . $this->table . ' : ' . $mysqli->errno . ' ' . $mysqli->error);
        if($this->log) echo date('Y-m-d H:i:s') . " Truncate a la tabla " .  $this->table . " hecho.\n";
      }

      foreach($files as $file) {
        $fileLocal = $localPathTransfer . DIRECTORY_SEPARATOR . $file;

        $loadData = "LOAD DATA LOCAL INFILE '" . $fileLocal . "'" . (!empty($this->replace) ? " REPLACE " : " ") . "INTO TABLE " . $this->table
        . " FIELDS TERMINATED BY '" . $this->separator
        . "' OPTIONALLY ENCLOSED BY '" . $this->enclosure
        . "' IGNORE " . $this->ignoreLines . " LINES";
        $queryResult = $mysqli->query($loadData);

        if(!$queryResult) throw new Exception('Error cargando el fichero ' .  basename($fileLocal) . ' a la tabla ' . $this->table . ' : ' . $mysqli->errno . ' ' . $mysqli->error);
        unlink($fileLocal);
        if($this->log) echo date('Y-m-d H:i:s') . " Fichero saliente " .  basename($fileLocal) . " cargado a la tabla " . $this->table . ".\n";
      }
    }
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";
  }
}
?>
