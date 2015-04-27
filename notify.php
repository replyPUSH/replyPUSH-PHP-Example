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


// get an array of error messages
require('./locale/errormessages.php');

/**
 * @@ denied @@
 * 
 * Deny invalid notifications
 * 
 * @return void
 */
function denied(){
    header("HTTP/1.0 403 Denied");
    exit();
}

/**
 * @@ processError @@
 * 
 * Get long error message and send error email
 * 
 * @param string $error the short hand error message
 * @param object $user 
 * @param string $references
 * @param string $subject
 * 
 * @return void
 */

function processError($error, $email, $name, $subject){
    $hasErrorMessage = array_key_exists($error, $errorMessages);
    if($hasErrorMessage)
        sendError($email, $name, $errorMessages[$error], $subject);
}

/**
 * @@ sendError @@
 * 
 * Send error email
 * 
 * @param string $email
 * @param string $name
 * @param string $errorMessage
 * @param string $subject
 * 
 * @return void
 */

function sendError($email, $name, $errorMessage, $subject){

    $errorPrefix =  <<<EOT
An error has occurred:

EOT;
    $errorSuffix = <<<EOT

Reply to the original message not this one

Thank you
EOT;
    
    // message 
    $message = Swift_Message::newInstance()
        ->setTo(array($email=>$name))
        ->setSubject("{$subjectPrefix} {$subject}")
        ->setFrom(array($siteEmail => $siteName))
        ->setContentType('text/plain')
        ->setCharset('utf-8')
        ->setBody("{$errorPrefix}{$errorMessage}{$errorSuffix}");
        
    // send message
    $result = $mailer->send($message);

}

/**
 * @@ saveReply @@
 * 
 * Save comment to log
 * 
 * @param string $email
 * @param string $name
 * @param array[string]string|array $notification
 * 
 * @return void
 */

function saveReply($email, $name, $content, $subject){
   if(isset($content['text/html'])){
       $content = $content['text/html'];
   }else if(isset($content['text/plain'])){
       $content = $content['text/plain'];
   }
   $comment = <<<EOT
Comment by {$name} <{$email}> in reply to "$subject":
{$content}
----------------------------------------------------

EOT;
   file_put_contents('./log/comments.txt', $comment , FILE_APPEND);
}

/*
* PROCESSING BEGINS
*/

$notification = $_POST;
// need postback
if(empty($notification)) return;

// need msg_id and in_reply_to
if(!isset($notification['msg_id']) || 
   !isset($notification['in_reply_to'])) return;

// DO THIS HERE: check that  msg_id is not already stored in your database

// check user exists if not deny
if($notification['from'] != $email )
   denied();
   

// use API class to check reference
// in_reply_to is original message Message-ID
// will detect hash method from reference
$replyPush = new ReplyPush(
    $accountNo,
    $secretID,
    $secretKey,
    $email,
    $notification['in_reply_to'] // can include reference in place of data
);

if($replyPush->hashCheck()){
    //split 56 bytes into 8 byte components and process
    $messageData = str_split($replyPush->referenceData,8);
    $fromUserID = hexdec($messageData[2]);
    $recordID = hexdec($messageData[3]);
    $type = trim($messageData[4]);

    // error handling
    if(isset($notification['error'])){
        processError(
            $email,
            $name,
            $notification['error'],
            $notification['subject']
        );
        return;
    }

    // check context type
    if($type == 'newdisc'){

        // DO THIS HERE: you want to check that is a valid discussion
        // with the $fromUserID (original poster) and $recordID
        // and the user replying is allowed to do so.

        // REMEMBER: you can send community specific errors via sendError()

        // REMEMBER: input and output sanitation and processing 
        // is your responsibility.  
        // The service does nothing to cleanse but try to strip out 
        // parts of the email which aren't part of the reply. 

       // save reply in context
       if(isset($notification['content']))
           saveReply(
               $name, 
               $email, 
               $notification['content'],
               $notification['subject']
           );
    }
}else{
   denied();
}

// DO THIS HERE:  store msg_id and $notification (serialized)
// for simple reference
// probably want to unset($notification['content']) first
