<?php
class InboundChannelFtp
{
  private $user;
  private $password;
  private $host;
  private $path;
  private $prefix;

  private $localAuxPath;
  private $localAuxFileTracker;

  private $waitLoop = 5;

  public function __construct($user, $password, $host, $path, $prefix = NULL)
  {
    $this->user = $user;
    $this->password = $password;
    $this->host = $host;
    $this->path = $path;
    $this->prefix = isset($prefix) ? explode(",", $prefix) : NULL;

    $this->localAuxPath = ROOT_PATH . DIRECTORY_SEPARATOR . '.' . $user . $host . str_replace('\\', '_', str_replace('/', '-', $path));
    $this->localAuxFileTracker = $this->localAuxPath . DIRECTORY_SEPARATOR . 'fileTracker.log';
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

  public function getFiles()
  {
    $remoteFiles = array();
    $localFiles = array();

    if(!file_exists($this->localAuxPath)) {
      if(!mkdir($this->localAuxPath, 0700)) throw new Exception('No se ha podido crear el directorio local auxiliar ' + $this->localAuxPath);
    }
    if(!file_exists($this->localAuxFileTracker)) {
      if(!touch($this->localAuxFileTracker)) throw new Exception('No se ha podido crear el fichero local auxiliar ' + $this->localAuxFileTracker);
    }

    if(!($ftpStream = ftp_connect($this->host))) throw new Exception('No se ha podido conectar a ' + $this->host + '.');
    if(!ftp_login($ftpStream, $this->user, $this->password)) throw new Exception('No se ha podido hacer login.');
    if(!ftp_chdir($ftpStream, $this->path)) throw new Exception('No se ha podido acceder al directorio remoto ' + $this->path);
    $remoteFiles = ftp_nlist($ftpStream, '.');

    foreach($remoteFiles as $remoteFile) {
      if($this->matchFile($remoteFile) && strpos(file_get_contents($this->localAuxFileTracker),$remoteFile) === false) {
        $localFile = $this->localAuxPath . DIRECTORY_SEPARATOR . $remoteFile;

        sleep($this->waitLoop);
        
        if(ftp_get($ftpStream, $localFile, $remoteFile, FTP_BINARY)) {
          $localFiles[] = $localFile;
          file_put_contents($this->localAuxFileTracker, $remoteFile . ';', FILE_APPEND | LOCK_EX);
        }
      }
    }

    ftp_close($ftpStream);

    return $localFiles;
  }
}
?>
