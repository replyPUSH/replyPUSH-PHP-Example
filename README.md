# replyPush - Reply By Email Example #

### About ###

Basic example of how you might use the replyPush API

### Installation / Configuration ###

Upload to you web folder, and ensure you have php handling for the directory. 

You need to create your output file `log/comments.txt` which will act as a simple store.

Make sure it has write access to the web user. 

Then rename `config/config-template.php` as `config/config.php` filling out all the values

Get your credentials from here: 

http://beta.replypush.com/profile

Fill out valid SMTP account credentials, and make sure all emails you use are valid.

Pick a suitable username for the test.

You need to save the public address `notify.php` e.g. `http://insectnuts.com/notify.php` here:

http://beta.replypush.com/profile


### Use ###

Point your browser to `send.php` or run in shell.

You could either `$ tail -f log/comments.txt` or point your browser to  `index.html`

Go to the destination email client, and await new mail.

Once arrived you can reply to the email. 

You see the message received back at the server. 

To clear message(s) you could `$ echo "" > log/comments.txt`








 



