<?php
  define('ROOT_PATH', __DIR__);
  set_time_limit(21600);

  spl_autoload_register(function ($className) {
    if (strpos($className, 'Channel'))
      include ROOT_PATH . DIRECTORY_SEPARATOR . 'channels' . DIRECTORY_SEPARATOR . $className . '.php';
  });

  function getParam($param, $required = true) {
    $params = getopt(null,array($param . ":"));
    if($required && !array_key_exists($param, $params)) throw new Exception('Falta el parámetro ' . $param . '.');
    if(!isset($params[$param])) return null;
    else return $params[$param];
  }

  switch(getParam('inbound-channel')) {
    case 'ftp':
      $inboundChannel = new InboundChannelFTP(
        getParam('inbound-user'),
        getParam('inbound-password'),
        getParam('inbound-host'),
        getParam('inbound-path'),
        getParam('inbound-prefix', false)
      );
      break;
    case 'sftp':
      $inboundChannel = new InboundChannelSFTP(
        getParam('inbound-user'),
        getParam('inbound-password'),
        getParam('inbound-host'),
        getParam('inbound-path'),
        getParam('inbound-prefix', false)
      );
      break;
    case 'mysql-query':
      $inboundChannel = new InboundChannelMySQLQuery(
        getParam('inbound-user'),
        getParam('inbound-password'),
        getParam('inbound-host'),
        getParam('inbound-database'),
        getParam('inbound-charset'),
        getParam('inbound-query'),
        getParam('inbound-filename')
      );
      break;
    default:
      throw new Exception('inbound-channel desconocido.');
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
    case 'ftp':
      $outboundChannel = new OutboundChannelFTP(
        getParam('outbound-user'),
        getParam('outbound-password'),
        getParam('outbound-host'),
        getParam('outbound-path')
      );
      break;
    default:
      throw new Exception('outbound-channel desconocido.');
      break;
  }

  $outboundChannel->putTransfer($inboundChannel->getTransfer($inboundChannel->getProposalLocalPath() . $outboundChannel->getProposalLocalPath()));
?>
