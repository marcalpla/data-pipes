<?php
require ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'aws' . DIRECTORY_SEPARATOR . 'aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;

class OutboundChannelS3
{
  private $s3;

  private $key;
  private $region;
  private $bucket;
  private $path;

  private $waitLoop = 5;
  private $log = true;
  private $maxFileSizeForSingleUploadinMB = 100;

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
    $this->path = rtrim($path, '/');
  }

  public function getProposalLocalPath()
  {
    return '.' . $this->key . $this->region . $this->bucket . str_replace("/", '-', $this->path);;
  }

  public function putTransfer($localPathTransfer, $s3StorageClass = 'STANDARD', $s3ACL = 'private')
  {
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " iniciado.\n";

    foreach(array_diff(scandir($localPathTransfer), array(".", "..")) as $file) {
      $file = $localPathTransfer . DIRECTORY_SEPARATOR . $file;
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $filesize = filesize($file);

      sleep($this->waitLoop);

      if($filesize < 1024 * 1024 * 5 || $filesize < 1024 * 1024 * $this->maxFileSizeForSingleUploadinMB) {
        $result = $this->s3->putObject(array(
          'Bucket'       => $this->bucket,
          'Key'          => $this->path . "/" . basename($file),
          'SourceFile'   => $file,
          'ContentType'  => finfo_file($finfo, $file),
          'StorageClass' => $s3StorageClass,
          'ACL'          => $s3ACL
        ));
      } else {
        $uploader = new MultipartUploader($this->s3, $file, array(
          'bucket' => $this->bucket,
          'key' => $this->path . "/" . basename($file),
          'before_initiate' => function (\Aws\Command $command) {
            $command['ContentType']  = finfo_file($finfo, $file);
            $command['StorageClass'] = $s3StorageClass;
            $command['ACL']          = $s3ACL;
          }
        ));
        do {
          try {
            $result = $uploader->upload();
          } catch (MultipartUploadException $e) {
            $uploader = new MultipartUploader($this->s3, $file, array(
              'state' => $e->getState(),
            ));
          }
        } while(!isset($result));
      }

      finfo_close($finfo);
      unlink($file);
      if($this->log) echo date('Y-m-d H:i:s') . " Fichero saliente " .  basename($file) . " transferido.\n";
    }
    if($this->log) echo date('Y-m-d H:i:s') . " Canal " . get_class($this) . " finalizado.\n";
  }
}
?>
