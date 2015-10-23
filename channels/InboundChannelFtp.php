<?php
class InboundChannelFtp {
  private $user;
  private $password;
  private $host;
  private $path;

  private $localAuxPath;
  private $localAuxFileTracker;

  private function getFtpMode($file)
  {
    $path_parts = pathinfo($file);

    if (!isset($path_parts['extension'])) return FTP_BINARY;

    switch (strtolower($path_parts['extension'])) {
      case 'am':case 'asp':case 'bat':case 'c':case 'cfm':case 'cgi':case 'conf':
      case 'cpp':case 'css':case 'csv':case 'dhtml':case 'diz':case 'h':case 'hpp':case 'htm':
      case 'html':case 'in':case 'inc':case 'js':case 'log':case 'm4':case 'mak':case 'nfs':
      case 'nsi':case 'pas':case 'patch':case 'php':case 'php3':case 'php4':case 'php5':
      case 'phtml':case 'pl':case 'po':case 'py':case 'qmail':case 'sh':case 'shtml':
      case 'sql':case 'tcl':case 'tpl':case 'txt':case 'vbs':case 'xml':case 'xrc':
        return FTP_ASCII;
    }

    return FTP_BINARY;
  }

  public function __construct($user, $password, $host, $path) {
    $this->user = $user;
    $this->password = $password;
    $this->host = $host;
    $this->path = $path;

    $this->localAuxPath = ROOT_PATH . DIRECTORY_SEPARATOR . '.' . $user . $host . str_replace('\\', '_', str_replace('/', '-', $path));
    $this->localAuxFileTracker = $this->localAuxPath . DIRECTORY_SEPARATOR . 'fileTracker.log';
  }

  public function getFiles() {
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
    if(!ftp_chdir($ftpStream,$this->$path)) throw new Exception('No se ha podido acceder al directorio remoto ' + $this->path);
    $remoteFiles = ftp_nlist($ftpStream, '.');

    foreach($remoteFiles as $remoteFile) {
      if(strpos(file_get_contents($this->localAuxFileTracker),$remoteFile) === false) {
        $localFile = $this->localAuxPath . DIRECTORY_SEPARATOR . $remoteFile;

        if(ftp_get($ftpStream, $localFile, $remoteFile, getFtpMode($remoteFile))) {
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
