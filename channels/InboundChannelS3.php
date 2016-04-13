<?php
require ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'aws' . DIRECTORY_SEPARATOR . 'aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class InboundChannelS3
{
  private $s3;

  private $key;
  private $region;
  private $bucket;
  private $path;

  private $localPath;
  private $localPathTransfer;

  private $log = true;

  public function __construct($key, $secret, $region, $bucket, $path)
  {
    $this->s3 = S3Client::factory(array(
      'credentials' => array(
        'key' => $key,
        'secret' => $secret
      ),
      'version' => '2006-03-01',
      'region' => $region
    ));

    $this->key = $key;
    $this->region = $region;
    $this->bucket = $bucket;
    $this->path = $path;
  }

  private function setLocalPath($localPath)
  {
    $this->localPath = $localPath;
    $this->localPathTransfer = $this->localPath . DIRECTORY_SEPARATOR . 'transfer';
  }

  public function getProposalLocalPath()
  {
    return '.' . $this->key . $this->region . $this->bucket . str_replace("/", '-', $this->path);
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

    $objects = $this->s3->getIterator('ListObjects', array(
      'Bucket'  => $this->bucket,
      'Prefix'  => $this->path
    ));

    foreach($objects as $object) {
      $localFileTmp = $this->localPath . DIRECTORY_SEPARATOR . "tmp-" . basename($object['Key']);
      $localFile = $this->localPathTransfer . DIRECTORY_SEPARATOR . basename($object['Key']);

      if(!($localFileTmpP = fopen($localFileTmp, 'wb'))) throw new Exception('No se ha podido crear el fichero temporal ' . $localFileTmp);
      $result = $this->s3->getObject(array(
        'Bucket'       => $this->bucket,
        'Key'          => $object['Key'],
        'SaveAs'       => $localFileTmpP
      ));

      if(filesize($localFileTmp) > 0) {
        if(!rename($localFileTmp, $localFile)) throw new Exception('Error moviendo el fichero temporal a definitivo: ' . $localFile);
        if($this->log) echo date('Y-m-d H:i:s') . " Fichero entrante " . basename($object['Key']) . " transferido.\n";
      } else {
        unlink($localFileTmp);
      }
    }

    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";
    return $this->localPathTransfer;
  }
}
?>
