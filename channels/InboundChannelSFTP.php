<?php
class InboundChannelSFTP
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
    $this->path = rtrim($path, DIRECTORY_SEPARATOR);
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

  private function file_get_contents_chunked($remoteFile, $localFile, $chunk_size, $callback)
  {
      try
      {
          $handle = fopen($remoteFile, "r");
          $i = 0;
          while (!feof($handle))
          {
              call_user_func_array($callback, array($localFile, fread($handle, $chunk_size), $i));
              $i++;
          }

          fclose($handle);

      }
      catch(Exception $e)
      {
           trigger_error("file_get_contents_chunked::" . $e->getMessage(),E_USER_NOTICE);
           return false;
      }
      return true;
  }

  public function getProposalLocalPath()
  {
    return '.' . $this->user . $this->host . str_replace(DIRECTORY_SEPARATOR, '-', $this->path);
  }

  public function getTransfer($localPath)
  {
    $remoteFiles = array();
    $localFiles = array();

    $this->setLocalPath($localPath);

    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " iniciado.\n";

    if(!file_exists($this->localPath)) {
      if(!mkdir($this->localPath, 0700)) throw new Exception('No se ha podido crear el directorio local auxiliar ' . $this->localPath);
    }
    if(!file_exists($this->localPathTransfer)) {
      if(!mkdir($this->localPathTransfer, 0700)) throw new Exception('No se ha podido crear el directorio local auxiliar para transferencias ' . $this->localPathTransfer);
    }
    if(!file_exists($this->localFileTracker)) {
      if(!touch($this->localFileTracker)) throw new Exception('No se ha podido crear el fichero local auxiliar ' . $this->localFileTracker);
    }

    if(!($sshStream = ssh2_connect($this->host, 22))) throw new Exception('No se ha podido conectar a ' . $this->host . '.');
    if(!ssh2_auth_password($sshStream, $this->user, $this->password)) throw new Exception('No se ha podido hacer login.');
    if(!($sftpStream = ssh2_sftp($sshStream))) throw new Exception('No se ha podido inicializar el subsistema SFTP.');
    if(!($remoteFiles = scandir('ssh2.sftp://' . $sftpStream . $this->path))) throw new Exception('No se ha podido acceder al directorio remoto ' . $this->path);

    foreach($remoteFiles as $remoteFile) {
      if($this->matchFile($remoteFile) && strpos(file_get_contents($this->localFileTracker),$remoteFile) === false) {
        $localFileTmp = $this->localPath . DIRECTORY_SEPARATOR . "tmp-" . $remoteFile;
        $localFile = $this->localPathTransfer . DIRECTORY_SEPARATOR . $remoteFile;
        sleep($this->waitLoop);
        if($this->file_get_contents_chunked("ssh2.sftp://". $sftpStream . $this->path . DIRECTORY_SEPARATOR . $remoteFile, $localFileTmp, 4096, function($localFile, $chunk, $i) {
            file_put_contents($localFile, $chunk, ($i == 0 ? LOCK_EX : (FILE_APPEND | LOCK_EX)));
        })) {
          if(!rename($localFileTmp, $localFile)) throw new Exception('Error moviendo el fichero temporal a definitivo: ' . $localFile);
          file_put_contents($this->localFileTracker, $remoteFile . ';', FILE_APPEND | LOCK_EX);
          if($this->log) echo date('Y-m-d H:i:s') . " Fichero entrante " . $remoteFile . " transferido.\n";
        }
      }
    }

    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";

    return $this->localPathTransfer;
  }
}
?>
