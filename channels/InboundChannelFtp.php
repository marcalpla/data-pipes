<?php
class InboundChannelFtp
{
  private $user;
  private $password;
  private $host;
  private $path;
  private $prefix;

  private $localPath;
  private $localPathTransfer;
  private $localFileTracker;

  private $waitLoop = 5;
  private $log = true;

  public function __construct($user, $password, $host, $path, $prefix = NULL)
  {
    $this->user = $user;
    $this->password = $password;
    $this->host = $host;
    $this->path = $path;
    $this->prefix = isset($prefix) ? explode(",", $prefix) : NULL;
  }

  private function matchFile($file) {
    $match = false;
    if(isset($this->prefix)) {
      foreach($this->prefix as $prefix) {
        if(!$match) $match = substr($file, 0, strlen($prefix)) === $prefix;
      }
    } else {
      $match = true;
    }
    return $match;
  }

  private function setLocalPath($localPath)
  {
    $this->localPath = $localPath;
    $this->localPathTransfer = $this->localPath . DIRECTORY_SEPARATOR . 'transfer';
    $this->localFileTracker = $this->localPath . DIRECTORY_SEPARATOR . 'fileTracker.log';
  }

  public function getProposalLocalPath()
  {
    return '.' . $this->user . $this->host . str_replace('\\', '_', str_replace('/', '-', $this->path));
  }

  public function getTransfer($localPath)
  {
    $remoteFiles = array();
    $localFiles = array();

    $this->setLocalPath($localPath);

    if($this->log) echo date('Y-m-d H:i:s') . " Canal InboundChannelFtp iniciado.\n";

    if(!file_exists($this->localPath)) {
      if(!mkdir($this->localPath, 0700)) throw new Exception('No se ha podido crear el directorio local auxiliar ' + $this->localPath);
    }
    if(!file_exists($this->localPathTransfer)) {
      if(!mkdir($this->localPathTransfer, 0700)) throw new Exception('No se ha podido crear el directorio local auxiliar para transferencias ' + $this->localPathTransfer);
    }
    if(!file_exists($this->localFileTracker)) {
      if(!touch($this->localFileTracker)) throw new Exception('No se ha podido crear el fichero local auxiliar ' + $this->localFileTracker);
    }

    if(!($ftpStream = ftp_connect($this->host))) throw new Exception('No se ha podido conectar a ' + $this->host + '.');
    if(!ftp_login($ftpStream, $this->user, $this->password)) throw new Exception('No se ha podido hacer login.');
    if(!ftp_chdir($ftpStream, $this->path)) throw new Exception('No se ha podido acceder al directorio remoto ' + $this->path);
    $remoteFiles = ftp_nlist($ftpStream, '.');

    foreach($remoteFiles as $remoteFile) {
      if($this->matchFile($remoteFile) && strpos(file_get_contents($this->localFileTracker),$remoteFile) === false) {
        $localFile = $this->localPathTransfer . DIRECTORY_SEPARATOR . $remoteFile;
        sleep($this->waitLoop);
        if(ftp_get($ftpStream, $localFile, $remoteFile, FTP_BINARY)){
          file_put_contents($this->localFileTracker, $remoteFile . ';', FILE_APPEND | LOCK_EX);
          if($this->log) echo date('Y-m-d H:i:s') . " Fichero entrante " . $remoteFile . " transferido.\n";
        }
      }
    }

    ftp_close($ftpStream);

    if($this->log) echo date('Y-m-d H:i:s') . " Canal InboundChannelFtp finalizado.\n";

    return $this->localPathTransfer;
  }
}
?>
