<?php

namespace phpListRestapi;

defined('PHPLISTINIT') || die;

/**
 * Class Statistiques
 * Manage phplist Statistics
 */
class Statistics {

    /**
     * <p>Get the statistics for the campaign without percentage</p>
     * <p><strong>Plugin required</strong><br/>
     * <a href=https://github.com/bramley/phplist-plugin-statistics/>MessageStatisticsPlugin</a>
     * </p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} The campaign id<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The campaign statistics.
     * </p>
     */
    static function statisticGet($id = 0) {
        if ($id == 0) {
            $id = $_REQUEST['id'];
        }

        $message = new \MessageStatisticsPlugin_DAO_Message(new \CommonPlugin_DB());
        $myStat = $message->fetchMessage($id, "", "@");

        $response = new Response();
        $response->setData('', $myStat);
        $response->output();
    }

    /**
     * <p>Get the statistics for the campaign with percentage like the advanced statistics</p>
     * <p><strong>Plugin required</strong><br/>
     * <a href=https://github.com/bramley/phplist-plugin-statistics/>MessageStatisticsPlugin</a>
     * </p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} The campaign id<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The campaign statistics.
     * </p>
     */
    static function statisticWithPercentageGet($id = 0) {
        $response = new Response();
        if ($id == 0) {
            $id = $_REQUEST['id'];
        }
        try {
            $controller = new \MessageStatisticsPlugin_Controller_Messages();
            $message = new \MessageStatisticsPlugin_DAO_Message(new \CommonPlugin_DB());
            $myStat = $message->fetchMessage($id, "", "@");
            $myStatPercentage = $controller->exportValues($myStat);
            $nbrTransmis = $message->totalMessageForwards($id);

            $row = array();
            $row['id'] = $myStatPercentage[0];
            $row['subject'] = $myStatPercentage[1];
            $row['datesent'] = $myStatPercentage[2];
            $row['sent'] = $myStatPercentage[3];
            $row['opens'] = $myStatPercentage[4];
            $row['openrate'] = $myStatPercentage[5];
            $row['clickUsers'] = $myStatPercentage[6];
            $row['clickrate'] = $myStatPercentage[7];
            $row['totalClicks'] = $myStatPercentage[8];
            $row['clickopenrate'] = $myStatPercentage[9];
            $row['bouncecount'] = $myStatPercentage[10];
            $row['bouncerate'] = $myStatPercentage[11];
            $row['forwardedcount'] = $nbrTransmis;
            $row['viewed'] = $myStatPercentage[12];
            $row['avgviews'] = $myStatPercentage[13];
            // Il faut retirer les bounces pour être en phase avec les pourcentages
            $row['unopens'] = $row['sent'] - $row['bouncecount'] - $row['opens'];
            $row['unopenrate'] = 100 - $row['openrate'];

            // Permet de calculer le nombre de mail en error
            $sql = "SELECT u.email, blda.data FROM phplist_user_user u LEFT JOIN phplist_user_blacklist_data blda ON u.email = blda.email ";
            $sql .= "WHERE u.id IN (SELECT userid FROM phplist_listuser WHERE listid IN (SELECT listid FROM phplist_listmessage WHERE messageid = " . $id . ")) ";
            $sql .= "AND u.email not in (SELECT email FROM phplist_user_user uu LEFT JOIN phplist_usermessage um ON uu.id = um.userid WHERE um.messageid = " . $id . ")";

            try {
                $db = PDO::getConnection();
                $stmt = $db->query($sql);
                $stmt->execute();
                $mails = $stmt->fetchAll(PDO::FETCH_OBJ);
                $db = null;
            } catch (PDOException $e) {
                
            }

            $row['error'] = count($mails);
            $row['countlinks'] = $message->totalLinks($id, '');

            $response->setData('', $row);
            $response->output();
        } catch (PDOException $ex) {
            Response::outputError($e);
        }
    }

    /**
     * <p>Get the statistics for all campaigns</p>
     * <p><strong>Plugin required</strong><br/>
     * <a href=https://github.com/bramley/phplist-plugin-statistics/>MessageStatisticsPlugin</a>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The statistics list.
     * </p>
     */
    static function statisticsGet() {
        $message = new \MessageStatisticsPlugin_DAO_Message(new \CommonPlugin_DB());
        $myStat = $message->fetchMessages("", "", false, null, null);

        $response = new Response();
        $response->setData('', $myStat);
        $response->output();
    }

    /**
     * <p>Get an email list for a campaign by stat type</p>
     * <p><strong>Plugin required</strong><br/>
     * <a href=https://github.com/bramley/phplist-plugin-statistics/>MessageStatisticsPlugin</a>
     * </p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} The campaign id<br/>
     * [type] {string} The stat type wanted (Values are : OPENED (Default), UNOPENED, CLICKED, BOUNCED, FORWARDED, ERROR)<br/>
     * [start] {int} The start number for the recovery<br/>
     * [limit] {int} The number of wanted mail<br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The email list.
     * </p>
     */
    static function mailingGet($id = 0, $type = 'OPENED', $start = -1, $limit = -1) {
        if ($id == 0) {
            $id = $_REQUEST['id'];
        }

        if ($type == 'OPENED') {
            $type = $_REQUEST['type'];
        }

        if ($start == -1) {
            $start = $_REQUEST['start'];
        }

        if ($limit == -1) {
            $limit = $_REQUEST['limit'];
        }

        $mails = array();

        if ($start < 0) {
            $start = null;
        }

        if ($limit < 0) {
            $limit = null;
        }

        $message = new \MessageStatisticsPlugin_DAO_Message(new \CommonPlugin_DB());

        switch ($type) {
            case "UNOPENED" :
                $mails = $message->fetchMessageOpens(false, $id, '', array(), '', '', $start, $limit);
                break;
            case "CLICKED" :
                $mails = $message->fetchMessageClicks($id, '', array(), '', '', $start, $limit);
                break;
            case "BOUNCED" :
                $mailsIterator = $message->fetchMessageBounces($id, '', array(), '', '', $start, $limit);

                // Get full bounce data
                $model = new \BounceStatisticsPlugin_Model(new \CommonPlugin_DB());
                $bounces = $model->listBounceReasons();

                // Get bounce reason only into an array
                $reason = array();
                foreach ($bounces as $bounce) {
                    $reason[$bounce['bounce']] = $bounce['reason'];
                }
                
                // Create mails array and replace bounce number by bounce reason
                $mails = array();
                foreach ($mailsIterator as $mail) {
                    $mail['bounce'] = $reason[$mail['bounce']];
                    $mails[] = $mail;
                }
                break;
            case "FORWARDED" :
                $mails = $message->fetchMessageForwards($id, '', array(), '', '', $start, $limit);
                break;
            case "ERROR" :
                if ($id == 0) {
                    $id = $_REQUEST['id'];
                }

                $sql = "SELECT u.email, blda.data FROM phplist_user_user u LEFT JOIN phplist_user_blacklist_data blda ON u.email = blda.email ";
                $sql .= "WHERE u.id IN (SELECT userid FROM phplist_listuser WHERE listid IN (SELECT listid FROM phplist_listmessage WHERE messageid = " . $id . ")) ";
                $sql .= "AND u.email not in (SELECT email FROM phplist_user_user uu LEFT JOIN phplist_usermessage um ON uu.id = um.userid WHERE um.messageid = " . $id . ")";
                $sql .= is_null($start) ? '' : "LIMIT $start, $limit";

                try {
                    $db = PDO::getConnection();
                    $stmt = $db->query($sql);
                    $mailsIterator = $stmt->fetchAll(PDO::FETCH_OBJ);
                    $db = null;
                } catch (PDOException $e) {
                    
                }

                // Get full bounce data
                $model = new \BounceStatisticsPlugin_Model(new \CommonPlugin_DB());
                $bounces = $model->listBounceReasons();

                // Get bounce reason only into an array
                $reason = array();
                foreach ($bounces as $bounce) {
                    $reason[$bounce['email']] = $bounce['reason'];
                }
                
                // Create mails array and replace bounce number by bounce reason
                $mails = array();
                foreach ($mailsIterator as $mail) {
                    if($reason[$mail->email]){
                        $mail->bounce = $reason[$mail->email];
                    }
                    $mails[] = $mail;
                }
                break;
            default :
                $mails = $message->fetchMessageOpens(true, $id, '', array(), '', '', $start, $limit);
                break;
        }

        $response = new Response();
        $response->setData('', $mails);
        $response->output();
    }

    /**
     * <p>Get the links list for a campaign.</p>
     * <p><strong>Plugin required</strong><br/>
     * <a href=https://github.com/bramley/phplist-plugin-statistics/>MessageStatisticsPlugin</a>
     * </p>
     * <p><strong>Parameters:</strong><br/>
     * [*id] {integer} The link id<br/>
     * <br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The links list.
     * </p>
     */
    static function linksGet($id) {
        if ($id == 0) {
            $id = $_REQUEST['id'];
        }

        $message = new \MessageStatisticsPlugin_DAO_Message(new \CommonPlugin_DB());

        $links = $message->links($id, '');

        $response = new Response();
        $response->setData('', $links);
        $response->output();
    }

    /**
     * <p>Get the email list for a clicked link</p>
     * <p><strong>Plugin required</strong><br/>
     * <a href=https://github.com/bramley/phplist-plugin-statistics/>MessageStatisticsPlugin</a>
     * </p>
     * <p><strong>Parameters:</strong><br/>
     * [*msgId] {integer} The campaign id<br/>
     * [*linkId] {integer} The link id<br/>
     * [start] {int} The start number for the recovery<br/>
     * [limit] {int} The wanted mail number<br/>
     * <br/>
     * </p>
     * <p><strong>Returns:</strong><br/>
     * The emails list.
     * </p>
     */
    static function mailingByLinkGet($msgId, $linkId, $start = -1, $limit = -1) {
        if ($msgId == 0) {
            $msgId = $_REQUEST['msgId'];
        }
        if ($linkId == 0) {
            $linkId = $_REQUEST['linkId'];
        }

        if ($start == -1) {
            $start = $_REQUEST['start'];
        }

        if ($limit == -1) {
            $limit = $_REQUEST['limit'];
        }

        if ($start < 0) {
            $start = null;
        }

        if ($limit < 0) {
            $limit = null;
        }

        $message = new \MessageStatisticsPlugin_DAO_Message(new \CommonPlugin_DB());

        $mails = $message->linkClicks($linkId, $msgId, '', '', '', '', $start, $limit);

        $response = new Response();
        $response->setData('', $mails);
        $response->output();
    }

}
