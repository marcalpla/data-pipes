<?php
require ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'aws' . DIRECTORY_SEPARATOR . 'aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class OutboundChannelS3
{
  private $s3;

  private $bucket;
  private $path;

  private $waitLoop = 5;

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

    $this->bucket = $bucket;
    $this->path = rtrim($path, '/') . '/';
  }

  public function putFile($file, $s3StorageClass = 'STANDARD', $s3ACL = 'private')
  {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    sleep($this->waitLoop);
    
    $result = $this->s3->putObject(array(
      'Bucket'       => $this->bucket,
      'Key'          => $this->path . basename($file),
      'SourceFile'   => $file,
      'ContentType'  => finfo_file($finfo, $file),
      'StorageClass' => $s3StorageClass,
      'ACL'          => $s3ACL
    ));
    finfo_close($finfo);
    unlink($file); var_dump($result);
  }
}
?>
