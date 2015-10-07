<?php

namespace phpListRestapi;

defined('PHPLISTINIT') || die;

class Subscribers {

    /**
     * <p>Get all the Subscribers in the system.</p>
     * <p><strong>Parameters:</strong><br/>
     * [order_by] {string} name of column to sort, default "id".<br/>
     * [order] {string} sort order asc or desc, default: asc.<br/>
     * [limit] {integer} limit the result, default 100.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * List of Subscribers.
     * </p>
     */
    static function subscribersGet($order_by = 'id', $order = 'asc', $limit = 100) {

        //getting optional values
        if (isset($_REQUEST['order_by']) && !empty($_REQUEST['order_by']))
            $order_by = $_REQUEST['order_by'];
        if (isset($_REQUEST['order']) && !empty($_REQUEST['order']))
            $order = $_REQUEST['order'];
        if (isset($_REQUEST['limit']) && !empty($_REQUEST['limit']))
            $limit = $_REQUEST['limit'];

        Common::select('Users', "SELECT * FROM " . $GLOBALS['usertable_prefix'] . "user ORDER BY $order_by $order LIMIT $limit;");
    }

    /**
     * <p>Get one given Subscriber.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} the ID of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * One Subscriber only.
     * </p>
     */
    static function subscriberGet($id = 0) {
        if ($id == 0)
            $id = $_REQUEST['id'];
        Common::select('User', "SELECT * FROM " . $GLOBALS['usertable_prefix'] . "user WHERE id = $id;", true);
    }

    /**
     * <p>Get one subscriber via email address.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * One Subscriber only.
     * </p>
     */
    static function subscriberGetByEmail($email = "") {
        if (empty($email))
            $email = $_REQUEST['email'];
        Common::select('User', "SELECT * FROM " . $GLOBALS['usertable_prefix'] . "user WHERE email = '$email';", true);
    }

    /**
     * <p>Add one Subscriber to the system.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * [*confirmed] {integer} 1=confirmed, 0=unconfirmed.<br/>
     * [*htmlemail] {integer} 1=html emails, 0=no html emails.<br/>
     * [*rssfrequency] {integer}<br/>
     * [*password] {string} The password to this Subscriber.<br/>
     * [*disabled] {integer} 1=disabled, 0=enabled<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The added Subscriber.
     * </p>
     */
    static function subscriberAdd() {

        $sql = "INSERT INTO " . $GLOBALS['usertable_prefix'] . "user (email, confirmed, htmlemail, rssfrequency, password, passwordchanged, disabled, entered, uniqid) VALUES (:email, :confirmed, :htmlemail, :rssfrequency, :password, now(), :disabled, now(), :uniqid);";
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("email", $_REQUEST['email']);
            $stmt->bindParam("confirmed", $_REQUEST['confirmed']);
            $stmt->bindParam("htmlemail", $_REQUEST['htmlemail']);
            $stmt->bindParam("rssfrequency", $_REQUEST['rssfrequency']);
            $stmt->bindParam("password", $_REQUEST['password']);
            $stmt->bindParam("disabled", $_REQUEST['disabled']);

            // fails on strict
#            $stmt->bindParam("uniqid", md5(uniqid(mt_rand())));

            $uniq = md5(uniqid(mt_rand()));
            $stmt->bindParam("uniqid", $uniq);
            $stmt->execute();
            $id = $db->lastInsertId();
            $db = null;
            Subscribers::SubscriberGet($id);
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Add subscribers.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*email] {array} the emails address of the subscribers to add.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * 'OK' if all subcribers are correctly inserted
     * </p>
     */
    static function subscribersAdd($emails) {
        if ($emails == 0)
            $emails = $_REQUEST['emails'];

        $tmp = "";
        foreach ($emails as $key => $email) {
            $tmp .= "'" . strtolower($email) . "',";
        }
        $tmp = preg_replace("/,$/", "", $tmp);

        $sqlMail = "SELECT DISTINCT REPLACE(LOWER(email), ' ', '') as email FROM " .
                $GLOBALS['usertable_prefix'] .
                "user WHERE REPLACE(LOWER(email), ' ', '') IN (" . $tmp . ")";

        try {
            $db = PDO::getConnection();
            $stmt = $db->query($sqlMail);
            $mails = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
        } catch (PDOException $ex) {
            Response::outputError($e);
        }

        $subscribers = $mails;

        $tmpEmails = array();
        foreach ($subscribers as $subscriber) {
            $tmpEmails[] = strtolower($subscriber->email);
        }

        // Query creation to insert data
        $sql = "";
        foreach ($emails as $key => $email) {
            // If it doesn't exist we add it
            if (!in_array(strtolower($email), $tmpEmails)) {
                $uniq = md5(uniqid(mt_rand()));
                $sql .= "(\"" . $email . "\", 1, 1, null, null, now(), 0, now(), \"" . $uniq . "\"),";
            }
        }
        $sql = preg_replace("/,$/", "", $sql);

        if ($sql) {
            $debutSQL = "INSERT INTO " . $GLOBALS['usertable_prefix'] . "user (email, confirmed, htmlemail, rssfrequency, password, passwordchanged, disabled, entered, uniqid) VALUES ";
            $sql = $debutSQL . $sql;

            try {
                $db = PDO::getConnection();
                $stmt = $db->query($sql);
                $stmt->execute();
                $db = null;
            } catch (PDOException $e) {
                Response::outputError($e);
            }
        }

        $tm = new \stdClass();
        $tm->insert = "OK";

        $result = array();
        $result[] = $tm;

        $response = new Response();
        $response->setData('', $result);
        $response->output();
    }

    /**
     * <p>Get a list of subscribers via email address.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*email] {array} the emails address of the Subscribers.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * An array of subscribers.
     * </p>
     */
    static function subscribersGetByEmail($emails) {
        if ($emails == 0)
            $emails = $_REQUEST['emails'];

        $tmp = "";
        foreach ($emails as $key => $email) {
            $tmp .= "'" . $email . "',";
        }
        $tmp = preg_replace("/,$/", "", $tmp);

        $sqlMail = "SELECT DISTINCT id, REPLACE(LOWER(email), ' ', '') as email FROM " .
                $GLOBALS['usertable_prefix'] .
                "user WHERE REPLACE(LOWER(email), ' ', '') IN (" . $tmp . ")";

        try {
            $db = PDO::getConnection();
            $stmt = $db->query($sqlMail);
            $subscribers = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            // delete doublon
            $subscribersFinal = array();
            foreach ($subscribers as $subscriber) {
                $subscribersFinal[$subscriber->email] = $subscriber->id;
            }
            return $subscribersFinal;
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Updates one Subscriber to the system.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} the ID of the Subscriber.<br/>
     * [*email] {string} the email address of the Subscriber.<br/>
     * [*confirmed] {integer} 1=confirmed, 0=unconfirmed.<br/>
     * [*htmlemail] {integer} 1=html emails, 0=no html emails.<br/>
     * [*rssfrequency] {integer}<br/>
     * [*password] {string} The password to this Subscriber.<br/>
     * [*disabled] {integer} 1=disabled, 0=enabled<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The updated Subscriber.
     * </p>
     */
    static function subscriberUpdate() {

        $sql = "UPDATE " . $GLOBALS['usertable_prefix'] . "user SET email=:email, confirmed=:confirmed, htmlemail=:htmlemail, rssfrequency=:rssfrequency, password=:password, passwordchanged=now(), disabled=:disabled WHERE id=:id;";

        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("id", $_REQUEST['id']);
            $stmt->bindParam("email", $_REQUEST['email']);
            $stmt->bindParam("confirmed", $_REQUEST['confirmed']);
            $stmt->bindParam("htmlemail", $_REQUEST['htmlemail']);
            $stmt->bindParam("rssfrequency", $_REQUEST['rssfrequency']);
            $stmt->bindParam("password", $_REQUEST['password']);
            $stmt->bindParam("disabled", $_REQUEST['disabled']);
            $stmt->execute();
            $db = null;
            Subscribers::SubscriberGet($_REQUEST['id']);
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Deletes a Subscriber.</p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} the ID of the Subscriber.<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The deleted Subscriber ID.
     * </p>
     */
    static function subscriberDelete() {

        $sql = "DELETE FROM " . $GLOBALS['usertable_prefix'] . "user WHERE id=:id;";
        try {
            $db = PDO::getConnection();
            $stmt = $db->prepare($sql);
            $stmt->bindParam("id", $_REQUEST['id']);
            $stmt->execute();
            $db = null;
            Response::outputDeleted('Subscriber', $_REQUEST['id']);
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Get the blacklisted mails and the unsubscribed mails</p>
     * 
     * <p><strong>Returns:</strong><br/>
     * The mails list
     */
    static function unsubscribeGet() {
        global $bounce_mailbox_purge;
        $sql = "SELECT email FROM " . $GLOBALS['usertable_prefix'] . "user "
                . "WHERE email IN (SELECT email FROM " . $GLOBALS['usertable_prefix'] . "blacklist) "
                . "OR `bouncecount` >= " . $bounce_mailbox_purge ." "
                . "OR `blacklisted` = 1 "
                . "OR email IN(SELECT email FROM phplist_linktrack_uml_click  AS uml JOIN phplist_user_user AS u ON uml.userid = u.id JOIN phplist_linktrack_forward AS fw ON fw.id = uml.forwardid WHERE fw.url LIKE '%unsubscribe')";

        try {
            $db = PDO::getConnection();
            $stmt = $db->query($sql);
            $unsubscribers = $stmt->fetchAll(PDO::FETCH_OBJ);
            $db = null;
            // delete doublon
            $unsubscribersFinal = array();
            foreach ($unsubscribers as $unsubscriber) {
                $unsubscribersFinal[] = $unsubscriber->email;
            }

            $response = new Response();
            $response->setData('', $unsubscribersFinal);
            $response->output();
        } catch (PDOException $e) {
            Response::outputError($e);
        }
    }

}
