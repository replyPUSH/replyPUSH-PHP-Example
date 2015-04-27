<?php
// replyPUSH API class
require('./library/class.replypush.php');

// get your replyPUSH credentials ($accountNo, $secretID, $secretKey)
// plus stmp details and faked user
require('./config/config.php');

// swiftmailer
require('./library/swiftmailer/lib/swift_required.php');

// transport method
$transport = Swift_SmtpTransport::newInstance($smtpHost, $smtpPort)
  ->setUsername($smtpUser)
  ->setPassword($smtpPass);
  
// mailer instance
$mailer = Swift_Mailer::newInstance($transport);

// dummy post
$subject      = 'How many legs?';
$slug         = 'how-many-legs';
$category     = 'Arthropods';
$postContent  = 'Do centipedes have 100 legs or what?';
$fromUserID   = 86;
$fromUserName = 'Ignorant';
$recordID     = 125;
$type         = 'newdisc';

$message = <<<EOT
<p>{$fromUserName} started a new discussion, ‘{$subject}’ in {$category}:</p>
<blockquote>{$postContent}</blockquote>
<p>You can check it out <a href="http://insectnuts.com/forum/discussion/{$recordID}/{$slug}">here</a></p>
<p></p>
<p>Have a great day!</p>
EOT;


// subject prefix
$subjectPrefix = "[{$siteName}] {$fromUserName} started a new discussion:";

//message prefix
$messagePrefix = '<a name="rp-message"></a><a href="http://replypush.com#rp-message"><wbr></a>';

// message suffix
$messageSuffix = <<<EOT
<p><br><b>***** reply service *****</b><br><br></p>
<p><b>You can reply by the link provided, or reply directly to this email.</b></p>
<p><span>Please put your message directly ABOVE the quoted message you get when you click reply.</span></p>
<p>Thank You.</p>
EOT;

//just noise
$timeStamp = time();
$randomSalt = mt_rand();

// custom 40 byte custom data 
$data = sprintf(
    "%08x%08x%-8s%08x%08x",
    $fromUserID,
    $recordID,
    $type,
    $timeStamp,
    $randomSalt
);

// lets use sha1 for hmac hash algorithm
$hashMethod = 'sha1';

// replyPUSH API class instance
$replyPush = new ReplyPush(
    $accountNo,
    $secretID,
    $secretKey,
    $email,
    $data,
    $hashMethod
);

// get reference that is verified by the service
// and will be used later for the reply notifications
$messageID = $replyPush->reference($withBrakets = FALSE);

// message 
$message = Swift_Message::newInstance()
    ->setTo(array($email=>$name))
    ->setSubject("{$subjectPrefix} {$subject}")
    ->setFrom(array($siteEmail => $siteName))
    ->setReplyTo(array(
        'post@replypush.net' => 'Ignorant [at] insectnuts.com'
    )) 
    ->setContentType('text/html') //HTML recommended
    ->setCharset('utf-8')
    ->setEncoder(Swift_Encoding::getQpEncoding())
    ->setBody("{$messagePrefix}{$message}{$messageSuffix}")
    ->setId($messageID);
    
$failures = array();
    
// send message
$sent = $mailer->send($message,$failures);

if(!$sent){
    echo "Email was not sent, the following failures occured: \n";
    foreach($failures As $failure)
        echo "  - {$failure}\n";
}else{
    echo "Email was sent succesfully!";
}



