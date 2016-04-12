<?php
require ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'aws' . DIRECTORY_SEPARATOR . 'aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class InboundChannelS3
{
  private $s3

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

    try {
      $result = $s3->getObject(array(
        'Bucket'       => $this->bucket,
        'Key'          => $this->path,
        'SaveAs'       => $this->localPathTransfer . DIRECTORY_SEPARATOR . basename($this->path);
      ));
      if($this->log) echo date('Y-m-d H:i:s') . " Fichero entrante " . basename($this->path) . " transferido.\n";
    } catch (S3Exception $e) {
      if($this->log) echo $e->getMessage() . "\n";
    }

    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";

    return $this->localPathTransfer;
  }
}
?>
