<?php
namespace App\Mail;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Exception\TransportException;
class DisabledTransport extends AbstractTransport {
 protected function doSend(SentMessage $message):void{throw new TransportException('Email delivery is not configured for this presentation.');}
 public function __toString():string{return 'disabled';}
}
