<?php
  define(ROOT_PATH, __DIR__);

  function __autoload($className) {
      if (strpos($className, 'Channel'))
        include ROOT_PATH . DIRECTORY_SEPARATOR . 'channels' . DIRECTORY_SEPARATOR . $className . '.php';
  }

  function getParam($param) {
    $params = getopt(null,array($param . ":"));
    if(!array_key_exists($param, $params)) throw new Exception('Falta el parámetro ' + $param + '.');
    return $params[$param];
  }

  switch(getParam('inbound-channel')) {
    case 'ftp':
      $inboundChannel = new InboundChannelFtp(
        getParam('inbound-user'),
        getParam('inbound-password'),
        getParam('inbound-host'),
        getParam('inbound-path')
      );
      break;
    case 'mysql-query':
      $inboundChannel = new InboundChannelMysqlQuery();
      break;
    default:
      throw new Exception('inbound_channel desconocido.');
      break;
  }

  switch(getParam('outbound-channel')) {
    case 's3':
      $outboundChannel = new OutboundChannelS3(
        getParam('outbound-key'),
        getParam('outbound-secret'),
        getParam('outbound-bucket'),
        getParam('outbound-path')
      );
      break;
    default:
      throw new Exception('outbound desconocido.');
      break;
  }

  foreach($inboundChannel->getFiles() as $file)
    $outboundChannel->putFile($file);
?>
