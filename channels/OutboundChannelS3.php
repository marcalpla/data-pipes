<?php
require ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'aws' . DIRECTORY_SEPARATOR . 'aws-autoloader.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class OutboundChannelS3 {
  private $s3;

  private $bucket;
  private $path;

  public function __construct($key, $secret, $bucket, $path) {
    $this->s3 = S3Client::factory(
      'credentials' => array(
        'key' => $key,
        'secret' => $secret
      )
    );

    $this->bucket = $bucket;
    $this->path = rtrim($path, '/') . '/';
  }

  public function putFile($file, $s3StorageClass = 'STANDARD', $s3ACL = 'private') {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $result = $s3->putObject(array(
        'Bucket'       => $this->bucket,
        'Key'          => $this->path . basename($file),
        'SourceFile'   => $file,
        'ContentType'  => finfo_file($finfo, $file),
        'ACL'          => $s3ACL,
        'StorageClass' => $s3StorageClass
    ));
    finfo_close($finfo);
    unlink($file);
  }
}
?>
