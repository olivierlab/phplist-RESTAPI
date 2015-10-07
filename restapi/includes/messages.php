<?php

namespace phpListRestapi;

defined('PHPLISTINIT') || die;

/**
 * Class Messages
 * Manage phplist Messages
 */
class Messages {

    private $url;
    private $c;

    /**
     * <p>Get a message/campaing.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {The message ID} <br/>
     * <p><strong>Returns:</strong><br/>
     * The message.
     * </p>
     */
    static function messageGet($id = 0) {
        if ($id == 0)
            $id = $_REQUEST['id'];
        Common::select('Message', "SELECT * FROM " . $GLOBALS['table_prefix'] . "message WHERE id=" . $id . ";", true);
    }
    
    /**
     * <p>Get all message/campaing.</p>
     * <p><strong>Parameters:</strong><br/>
     * (none) <br/>
     * <p><strong>Returns:</strong><br/>
     * An array of message.
     * </p>
     */
    static function messagesGet() {
        Common::select('Messages', "SELECT * FROM " . $GLOBALS['table_prefix'] . "message ORDER BY Modified DESC;");
    }

    /**
     * <p>Adds a new message/campaing.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*subject] {string} <br/>
     * [*fromfield] {string} <br/>
     * [*replyto] {string} <br/>
     * [*message] {string} <br/>
     * [*textmessage] {string} <br/>
     * [*footer] {string} <br/>
     * [*status] {string} <br/>
     * [*sendformat] {string} <br/>
     * [*template] {string} <br/>
     * [*embargo] {string} <br/>
     * [*rsstemplate] {string} <br/>
     * [*owner] {string} <br/>
     * [htmlformatted] {string} <br/>
     * [*finishsending] {date} <br/>
     * <p><strong>Returns:</strong><br/>
     * The message added.
     * </p>
     */
    static function messageAdd() {

        $sql = "INSERT INTO " . $GLOBALS['table_prefix'] . "message (subject, fromfield, replyto, message, textmessage, footer, entered, status, sendformat, template, embargo, rsstemplate, owner, htmlformatted ) VALUES ( :subject, :fromfield, :replyto, :message, :textmessage, :footer, now(), :status, :sendformat, :template, :embargo, :rsstemplate, :owner, :htmlformatted );";
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("subject", base64_decode($_REQUEST['subject']));
            $stmt->bindParam("fromfield", $_REQUEST['fromfield']);
            $stmt->bindParam("replyto", $_REQUEST['replyto']);
            $stmt->bindParam("message", Messages::getField('message'));
            $stmt->bindParam("textmessage", base64_decode($_REQUEST['textmessage']));
            $stmt->bindParam("footer", Messages::getField('footer'));
            $stmt->bindParam("status", $_REQUEST['status']);
            $stmt->bindParam("sendformat", $_REQUEST['sendformat']);
            $stmt->bindParam("template", $_REQUEST['template']);
            $stmt->bindParam("embargo", $_REQUEST['embargo']);
            $stmt->bindParam("rsstemplate", $_REQUEST['rsstemplate']);
            $stmt->bindParam("owner", $_REQUEST['owner']);
            $stmt->bindParam("htmlformatted", $_REQUEST['htmlformatted']);
            $stmt->execute();
            $id = $db->lastInsertId();

            // Create table messagedata
            Messages::messageDataUpdate($db, $id);

            $db = null;

            Messages::messageGet($id);
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Updates existing message/campaign.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} <br/>
     * [*subject] {string} <br/>
     * [*fromfield] {string} <br/>
     * [*replyto] {string} <br/>
     * [*message] {string} <br/>
     * [*textmessage] {string} <br/>
     * [*footer] {string} <br/>
     * [*status] {string} <br/>
     * [*sendformat] {string} <br/>
     * [*sendstart] {string} <br/>
     * [*template] {string} <br/>
     * [*embargo] {string} <br/>
     * [*rsstemplate] {string} <br/>
     * [owner] {string} <br/>
     * [htmlformatted] {string} <br/>
     * [*finishsending] {date} <br/>
     * <p><strong>Returns:</strong><br/>
     * The message added.
     * </p>
     */
    static function messageUpdate($id = 0) {

        if ($id == 0)
            $id = $_REQUEST['id'];

        $sql = "UPDATE " . $GLOBALS['table_prefix'] . "message SET subject=:subject, fromfield=:fromfield, replyto=:replyto, message=:message, textmessage=:textmessage, footer=:footer, status=:status, sendformat=:sendformat, template=:template, sendstart=:sendstart, rsstemplate=:rsstemplate, owner=:owner, htmlformatted=:htmlformatted, embargo=:embargo WHERE id=:id;";

        try {
            $db = PDO::getConnection();

            $stmt = $db->prepare($sql);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":subject", base64_decode($_REQUEST['subject']));
            $stmt->bindParam(":fromfield", $_REQUEST['fromfield']);
            $stmt->bindParam(":replyto", $_REQUEST['replyto']);
            $stmt->bindParam(":message", Messages::getField('message'));
            $stmt->bindParam(":textmessage", base64_decode($_REQUEST['textmessage']));
            $stmt->bindParam(":footer", Messages::getField('footer'));
            $stmt->bindParam(":status", $_REQUEST['status']);
            $stmt->bindParam(":sendformat", $_REQUEST['sendformat']);
            $stmt->bindParam(":template", $_REQUEST['template']);
            $stmt->bindParam(":sendstart", $_REQUEST['sendstart']);
            $stmt->bindParam(":rsstemplate", $_REQUEST['rsstemplate']);
            $stmt->bindParam(":owner", $_REQUEST['owner']);
            $stmt->bindParam(":htmlformatted", $_REQUEST['htmlformatted']);
            $stmt->bindParam(":embargo", $_REQUEST['embargo']);

            $stmt->execute();
            // Update table messagedata
            Messages::messageDataUpdate($db, $id);

            $db = null;

            Messages::messageGet($id);
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Updates existing message/campaign into messageData table.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*db] {pdo} The PDO<br/>
     * [*id] {integer} The campaign id<br/>
     * <p><strong>Returns:</strong><br/>
     * Nothing.
     * </p>
     */
    private static function messageDataUpdate($db, $id = 0) {

        if ($id == 0)
            $id = $_REQUEST['id'];

        Messages::messageDataOneUpdate($db, $id, "subject", base64_decode($_REQUEST['subject']));
        Messages::messageDataOneUpdate($db, $id, "fromfield", Messages::getField("fromfield"));
        Messages::messageDataOneUpdate($db, $id, "message", Messages::getField("message"));
        Messages::messageDataOneUpdate($db, $id, "footer", Messages::getField("footer"));
        Messages::messageDataOneUpdate($db, $id, "status", Messages::getField("status"));
        Messages::messageDataOneUpdate($db, $id, "sendformat", Messages::getField("sendformat"));
        Messages::messageDataOneUpdate($db, $id, "htmlformatted", Messages::getField("htmlformatted"));
        Messages::messageDataOneUpdate($db, $id, "embargo", Messages::getField("embargo"));
        Messages::messageDataOneUpdate($db, $id, "finishsending", Messages::getField("finishsending"));

        $dateEmbargo = Messages::getField("embargo");
        setMessageData($id, "embargo", array('year' => date('Y', strtotime($dateEmbargo)), 'month' => date('m', strtotime($dateEmbargo)), 'day' => date('d', strtotime($dateEmbargo)), 'hour' => date('H', strtotime($dateEmbargo)), 'minute' => date('i', strtotime($dateEmbargo))));

        $finsihsending = Messages::getField("finishsending");
        setMessageData($id, "finishsending", array('year' => date('Y', strtotime($finsihsending)), 'month' => date('m', strtotime($finsihsending)), 'day' => date('d', strtotime($finsihsending)), 'hour' => date('H', strtotime($finsihsending)), 'minute' => date('i', strtotime($finsihsending))));
    }

    /**
     * <p>Update one field into into messageData table.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*db] {pdo} <br/>
     * [*id] {integer} <br/>
     * [*field] {string} The field to update<br/>
     * <p><strong>Returns:</strong><br/>
     * Nothing.
     * </p>
     */
    private static function messageDataOneUpdate($db, $id, $field, $value) {
        try {
            $sql = "SELECT COUNT(1) as count FROM " . $GLOBALS['table_prefix'] . "messagedata WHERE id=" . $id . " AND name='" . $field . "';";
            $stmt2 = $db->prepare($sql);
            $stmt2->bindParam("id", $id);
            $stmt2->execute();
            $result = $stmt2->fetchAll(PDO::FETCH_OBJ);

            if ($result[0]->count >= 1) {
                $sql = "UPDATE " . $GLOBALS['table_prefix'] . "messagedata SET data=:" . $field . " WHERE id=:id AND name='" . $field . "';";
                $stmt2 = $db->prepare($sql);
                $stmt2->bindParam("id", $id);
                $stmt2->bindParam($field, $value);
                $stmt2->execute();
            } else {
                $sql = "INSERT INTO " . $GLOBALS['table_prefix'] . "messagedata (name, id, data) VALUES ('" . $field . "', :id, :" . $field . ")";
                $stmt2 = $db->prepare($sql);
                $stmt2->bindParam("id", $id);
                $stmt2->bindParam($field, $value);
                $stmt2->execute();
            }
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Get field.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*field] {string} The field to get<br/>
     * <p><strong>Returns:</strong><br/>
     * The wanted field.
     * </p>
     */
    private static function getField($field) {
        switch ($field) {
            case "message":
                $result = preg_replace("/\\\\\\\"/", '"', $_REQUEST['message']);
                $result = preg_replace("/\\\'/", "'", $result);
                break;
            case "footer":
                $result = preg_replace("/\\\'/", "'", $_REQUEST['footer']);
                break;
            default:
                $result = $_REQUEST[$field];
                break;
        }

        return $result;
    }

    /**
     * <p>Send existing message/campaign to queue for processing.</p><p>This campaign will only be proceed by batch.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} The campaign id<br/>
     * <p><strong>Returns:</strong><br/>
     * The message sended.
     * </p>
     */
    static function messageSend($id = 0) {

        if ($id == 0)
            $id = $_REQUEST['id'];
        $status = "submitted";
        $sql = "UPDATE " . $GLOBALS['table_prefix'] . "message SET status=:status WHERE id=:id;";
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("id", $id);
            $stmt->bindParam("status", $status);
            $stmt->execute();
            $db = null;
            Messages::messageGet($id);
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Send an existing message/campaign to an email for testing.</p>
     * <p><strong>Plugin required</strong><br/>
     * <a href=https://github.com/bramley/phplist-plugin-common/>Common Plugin</a>
     * <p><strong>Parameters:</strong><br/>
     * [*campaign_id] {integer} The campaign id<br/>
     * [*email] {string} The email<br/>
     * [*login] {string} The login<br/>
     * [*pwd] {string} The password<br/>
     * <p><strong>Returns:</strong><br/>
     * A message.
     * </p>
     */
    public static function messageSendTest($campaign_id = 0, $emails = '', $login = '', $pwd = '') {

        if ($campaign_id == 0)
            $campaign_id = $_REQUEST['id'];
        if ($emails == '')
            $emails = $_REQUEST['email'];
        if ($login == '')
            $login = $_REQUEST['login'];
        if ($pwd == '')
            $pwd = $_REQUEST['pwd'];

        // Get campaign information
        $message = new \MessageStatisticsPlugin_DAO_Message(new \CommonPlugin_DB());
        $result = $message->messageById($campaign_id);

        // Init data to POST
        $data['followupto'] = '';
        $data['sendmethod'] = 'inputhere';
        $data['sendtest'] = 'Tester+-+envoyer+un+message+de+test';
        $data['sendurl'] = 'e.g.+http://www.phplist.com/testcampaign.html';
        $data['id'] = $campaign_id;
        $data['subject'] = $result['subject'];
        $data['fromfield'] = $result['fromfield'];
        $data['message'] = $result['message'];
        $data['footer'] = $result['footer'];
        $data['status'] = $result['status'];

        // URL to call
        $url_base = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
        $cmd = "?page=send&id=" . $campaign_id;

        $c = Messages::connection($login, $pwd, $url_base);
        foreach ($emails as $key => $email) {
            $exist = Messages::isSubscriberExist($email);

            // If email not exist, we need to add it otherwise the test mail isn't sended
            if (!$exist)
                $exist = Messages::addSubscriber($email);

            // Send test email
            if ($exist) {
                $data['testtarget'] = $email;
                curl_setopt($c, CURLOPT_URL, $url_base . $cmd);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($c, CURLOPT_POST, 1);
                curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($data));
                $result = curl_exec($c);
            }
        }
        Messages::deconnection($c);

        Response::outputMessage("Message sended to emails");
    }

    /**
     * Test if email exist
     * @param type $email The email to test
     * @return boolean True if subscriber exist, false otherwise
     */
    private static function isSubscriberExist($email) {

        try {
            $db = PDO::getConnection();
            $stmt = $db->query("SELECT * FROM " . $GLOBALS['usertable_prefix'] . "user WHERE email = '$email';");
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            if (is_array($result) && isset($result[0]))
                $result = $result[0];
            return ($result->id ? true : false);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * <p>Adds one Subscriber to the system.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * True if added, false otherwise
     * </p>
     */
    private static function addSubscriber($email) {

        $sql = "INSERT INTO " . $GLOBALS['usertable_prefix'] . "user "
                . "(email, confirmed, htmlemail, rssfrequency, password, passwordchanged, disabled, entered, uniqid) "
                . "VALUES (:email, 1, 1, null, null, now(), 0, now(), :uniqid);";
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $email);
            $uniq = md5(uniqid(mt_rand()));
            $stmt->bindParam("uniqid", $uniq);
            $stmt->execute();
            $db = null;
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     *
     * @param type $login
     * @param type $pwd
     * @param type $url
     * @return type curl connection
     */
    private function connection($login, $pwd, $url) {
        //initialize cUrl for remote content
        $c = curl_init();
        curl_setopt($c, CURLOPT_COOKIEFILE, 'phpList_RESTAPI_Helper');
        curl_setopt($c, CURLOPT_COOKIEJAR, 'phpList_RESTAPI_Helper');
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, CURLOPT_POST, 1);
        
        //Call for the session-id via /login
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_POSTFIELDS, 'cmd=login&login=' . $login . '&password=' . $pwd);
        $result = curl_exec($c);
        $result = json_decode($result);
        return $c;
    }

    /**
     * Curl deconnection
     * @param type $c The curl connection
     */
    private function deconnection($c) {
        curl_close($c);
    }

    private static function formtokenGet() {
        $key = md5(time() . mt_rand(0, 10000));
        Sql_Query(sprintf('insert into %s (adminid,value,entered,expires) values(%d,"%s",%d,date_add(now(),interval 1 hour))', $GLOBALS['tables']['admintoken'], $_SESSION['logindetails']['id'], $key, time()), 1);
        $response = new Response();
        $response->setData('formtoken', $key);
        $response->output();
    }

    /**
     * <p>Delete a message/campaign.</p>
     * <p><strong>Plugin required</strong><br/>
     * <a href=https://github.com/bramley/phplist-plugin-common/>Common Plugin</a>
     * </p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} the ID of the campaign.
     * <p><strong>Returns:</strong><br/>
     * System message of action.
     * </p>
     */
    public static function messageDelete() {

        $message = new \MessageStatisticsPlugin_DAO_Message(new \CommonPlugin_DB());

        if ($message->deleteMessage($_REQUEST['id'])) {
            Response::outputDeleted('Campaign', $_REQUEST['id']);
        } else {
            Response::outputErrorMessage('Campaign data not deleted for id = ' . $_REQUEST['id']);
        }
        die(0);
    }

    /**
     * Redirect with POST data.
     * Source : http://stackoverflow.com/questions/5576619/php-redirect-with-post-data
     * @param string $url URL.
     * @param array $data POST data. Example: array('foo' => 'var', 'id' => 123)
     * @param array $headers Optional. Extra headers to send.
     */
    private static function redirect_post($url, array $data, array $headers = null) {
        $params = array(
            'http' => array(
                'method' => 'POST',
                'content' => http_build_query($data)
            )
        );
        if (!is_null($headers)) {
            $params['http']['header'] = '';
            foreach ($headers as $k => $v) {
                $params['http']['header'] .= "$k: $v\n";
            }
        }
        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if ($fp) {
            $retour = stream_get_contents($fp);
            var_dump($retour);
            return true;
        } else {
            // Error
            Response::outputError(Exception("Error loading '$url', $php_errormsg"));
        }
    }
}