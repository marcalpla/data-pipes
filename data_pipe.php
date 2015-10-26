<?php
  define('ROOT_PATH', __DIR__);
  set_time_limit(21600);

  spl_autoload_register(function ($className) {
    if (strpos($className, 'Channel'))
      include ROOT_PATH . DIRECTORY_SEPARATOR . 'channels' . DIRECTORY_SEPARATOR . $className . '.php';
  });

  function getParam($param, $required = true) {
    $params = getopt(null,array($param . ":"));
    if($required && !array_key_exists($param, $params)) throw new Exception('Falta el parámetro ' + $param + '.');
    if(!isset($params[$param])) return null;
    else return $params[$param];
  }

  switch(getParam('inbound-channel')) {
    case 'ftp':
      $inboundChannel = new InboundChannelFtp(
        getParam('inbound-user'),
        getParam('inbound-password'),
        getParam('inbound-host'),
        getParam('inbound-path'),
        getParam('inbound-prefix', false)
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
        getParam('outbound-region'),
        getParam('outbound-bucket'),
        getParam('outbound-path')
      );
      break;
    default:
      throw new Exception('outbound desconocido.');
      break;
  }

  $outboundChannel->putTransfer($inboundChannel->getTransfer($inboundChannel->getProposalLocalPath() . $outboundChannel->getProposalLocalPath()));
?>
