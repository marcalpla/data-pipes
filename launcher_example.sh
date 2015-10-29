#!/bin/bash
# FTP to AWS S3 data pipe example

INBOUND_CHANNEL=ftp
INBOUND_USER=value
INBOUND_PASSWORD=value
INBOUND_HOST=value
INBOUND_PATH=value
INBOUND_PREFIX=value

OUTBOUND_CHANNEL=s3
OUTBOUND_KEY=value
OUTBOUND_SECRET=value
OUTBOUND_REGION=value
OUTBOUND_BUCKET=value
OUTBOUND_PATH=value

DATAPIPE_PATH=value

/usr/bin/php $DATAPIPE_PATH --inbound-channel=$INBOUND_CHANNEL --inbound-user=$INBOUND_USER --inbound-password=$INBOUND_PASSWORD --inbound-host=$INBOUND_HOST --inbound-path=$INBOUND_PATH --inbound-prefix=$INBOUND_PREFIX
--outbound-channel=$OUTBOUND_CHANNEL --outbound-key=$OUTBOUND_KEY --outbound-secret=$OUTBOUND_SECRET --outbound-region=$OUTBOUND_REGION --outbound-bucket=$OUTBOUND_BUCKET --outbound-path=$OUTBOUND_PATH
