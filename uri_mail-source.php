function uri_mail($atts, $thing=NULL) {

  extract(lAtts(array(
        'mail_label' => 'Your email',
        'object_label' => 'Object',
        'message_label' => 'Your message',
        ), $atts));

  $form = "<form method='post' action='<txp:page_url />'>
             <label>$mail_label</label><br />
             <input type='text' maxlength='100' name='uriMail' /><br />
             <label>$object_label</label><br />
             <input type='text' maxlength='100' name='uriObject' /><br />
             <label>$message_label</label><br />
             <textarea cols='58' rows='8' name='uriMessage'></textarea><br />
             <input type='submit' name='uriSend' value='Send' />
           </form>";
  if (isset($_POST['uriSend'])) {
    $uriObject = $_POST['uriObject'];
    $uriMail = $_POST['uriMail'];
    $uriMessage = $_POST['uriMessage'];
    $uriMessage = uri_format($uriMessage);
    $query = "INSERT INTO uri_messages SET
                object = '$uriObject',
                mail = '$uriMail',
                message = '$uriMessage'
              ";
    if (@mysql_query($query)) {
      $form = "<p>I contacted the administrator. Wait for his reply!</p>";
    } else {
      $form = "<p>I couldn't contact the administrator. Wait and try again!</p>";
    }
  }
  return $form;
}

if (isset($_POST['uriSend'])) {
  $uriObject = $_POST['uriObject'];
  $uriMail = $_POST['uriMail'];
  $uriMessage = $_POST['uriMessage'];
  $query = "INSERT INTO uri_messages SET (
              object = '$uriObject',
              mail = '$uriMail',
              message = $uriMessage
              )";
}

if (@txpinterface == 'admin') {
  add_privs('uri_mail', '1'); // Publishers only
  register_tab('extensions', 'uri_mail', 'UriMail');
  register_callback('uri_mail_gui', 'uri_mail');
}

function uri_mail_gui($event, $step) {
 if(!$step or !in_array($step, array(
    'uri_mail_show',
    'uri_mail_delete',
 ))) {
    uri_mail_list();
 } else $step();
}

function uri_mail_list() {
  pagetop('UriMail tab');
  echo "<style type='text/css'>
          table.uri-list {
            border: 1px solid #cccccc;
            width: 100%;
          }
          table.uri-list tr {
            cursor: pointer;
          }
          table.uri-list td, table.uri-list th {
            padding: .5em 1.5em;
            border: 1px solid #cccccc;
          }
          table.uri-list tr:nth-child(odd) {
            background-color: #F3FFC1;
          }
          table.uri-list th {
            background-color: #EFFFAC;
          }
          table.uri-list tr:hover {
            background-color: #FFE2EE;
          }
          td.uri-show-link {
            display: none;
          }
        </style>
        <script type='text/javascript'>
          $(document).ready(function() {
            $('table.uri-list tr').click(function(){
              window.location=$(this).find('td.uri-show-link a').attr('href');
              return false;
            });
          });
        </script>";
  mysql_query("CREATE TABLE IF NOT EXISTS uri_messages (
                 id INT(10) NOT NULL AUTO_INCREMENT,
                 object TEXT,
                 mail TEXT,
                 message TEXT NOT NULL,
                 PRIMARY KEY (id)
  )");
  $query = mysql_query("SELECT id, object, mail, message FROM uri_messages ORDER BY id DESC");
  echo "<h1>Messages list</h1>";
  echo "<table class='uri-list'>
         <thead>
           <th>Id</th>
           <th>Object</th>
           <th>Mail</th>
           <th>Message</th>
         </thead>
       ";
  while ($row = mysql_fetch_array($query)) {
    $id = $row['id'];
    $object = htmlspecialchars($row['object']);
    $mail = htmlspecialchars($row['mail']);
    $message = uri_excerpt($row['message']);
    $message = stripslashes($message);
    echo "<tr>
            <td>$id</td>
            <td>$object</td>
            <td>$mail</td>
            <td>$message</td>
            <td class='uri-show-link'>".eLink('uri_mail', 'uri_mail_show', 'uri_id', $id, 'Show')."</td>
          </tr>";
  }
  echo "</table>";
  if (isset($_GET['uriId'])) {
    uri_mail_show();
  }
}

function uri_mail_show() {
  pagetop('UriMail - Single message');
  $uriId = $_GET['uri_id'];
  $message = uri_get_message_by_id($uriId);
  $uriObject = $message['object'];
  $uriMail = $message['mail'];
  $uriMessage = $message['message'];
  $uriMessage = stripslashes($uriMessage);
  echo "<h1>Message $uriId</h1>
        <p><a href='mailto:$uriMail'>$uriMail</a></p>
        <h2>$uriObject</h2>
        <p>$uriMessage</p>
        <p>".eLink('uri_mail', 'uri_mail_delete', 'uri_id', $uriId, 'Delete')."</p>";
}

function uri_mail_delete() {
  pagetop('UriMail - Delete message');
  $uriId = $_GET['uri_id'];
  $delete = "DELETE FROM uri_messages WHERE id='$uriId'";
  if (@mysql_query($delete)) {
    echo "<h1>Message $uriId deleted.</h1>
          <p><a href='?event=uri_mail'>Back</a></p>";
  } else {
    echo "<h1>Oops.</h1>
          <p>I couldn't delete the message. Are you sure I can reach the database?</p>
          <p><a href='?event=uri_mail'>Back</a></p>";
  }
}

function uri_get_message_by_id($id) {
  $sql = "SELECT * FROM uri_messages WHERE id='$id'";
  $query = mysql_query($sql);
  $message = mysql_fetch_array($query);
  return $message;
}

function uri_format($text) {
  $text = addslashes($text);
  $text = str_replace("\r", "", $text);
  $text = str_replace("\n", "<br />", $text);
  return $text;
}

function uri_excerpt($text) {
  $text = str_replace('<br />', ' ', $text);
  $text = strip_tags($text);
  $textArray = explode(' ', $text, 8);
  $count=0;
  $shortArray = array();
  while ($count < 7) {
    $shortArray[$count] = $textArray[$count];
    $count += 1;
  }
  return implode(' ', $shortArray);
}
