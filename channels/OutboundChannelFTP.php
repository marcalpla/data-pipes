<?php
class OutboundChannelFTP
{
  private $user;
  private $password;
  private $host;
  private $path;

  private $waitLoop = 5;
  private $log = true;

  public function __construct($user, $password, $host, $path)
  {
    $this->user = $user;
    $this->password = $password;
    $this->host = $host;
    $this->path = rtrim($path, DIRECTORY_SEPARATOR);
  }

  public function getProposalLocalPath()
  {
    return '.' . $this->user . $this->host . str_replace(DIRECTORY_SEPARATOR, '-', $this->path);
  }

  public function putTransfer($localPathTransfer)
  {
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " iniciado.\n";

    if(!($ftpStream = ftp_connect($this->host))) throw new Exception('No se ha podido conectar a ' . $this->host . '.');
    if(!ftp_login($ftpStream, $this->user, $this->password)) throw new Exception('No se ha podido hacer login.');

    ftp_pasv($ftpStream, true);

    if(!empty($this->path) && !ftp_chdir($ftpStream, $this->path)) throw new Exception('No se ha podido acceder al directorio remoto ' . $this->path);

    foreach(array_diff(scandir($localPathTransfer), array(".", "..")) as $file) {
      $fileRemote = $this->path . "/" . $file;
      $fileLocal = $localPathTransfer . DIRECTORY_SEPARATOR . $file;

      sleep($this->waitLoop);

      if(!ftp_put($ftpStream, $fileRemote, $fileLocal, FTP_BINARY)) throw new Exception('No se ha podido transferir el fichero hacia ' . $fileRemote);
      unlink($fileLocal);
      if($this->log) echo date('Y-m-d H:i:s') . " Fichero saliente " .  basename($fileLocal) . " transferido.\n";
    }
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";
  }
}
?>
