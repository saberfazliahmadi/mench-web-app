<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cron extends CI_Controller {
	
	function __construct() {
		parent::__construct();
		
		$this->output->enable_profiler(FALSE);

        //Example: /usr/bin/php /home/ubuntu/mench-web-app/index.php cron save_profile_pic
	}


    function algolia_sync($obj,$obj_id=0){
        echo_json($this->Db_model->algolia_sync($obj,$obj_id));
    }

    /* ******************************
     * Classes
     ****************************** */


    function update_u_e_score($u=array()){

        //Updates u_e_score based on number/value of connections to other intents/entities
        //Cron Settings: 2 * * * 30

        //Define weights:
        $score_weights = array(
            'u_inbound_u_id' => 0, //Child entities are just containers, no score on the link

            'e_outbound_u_id' => 1, //Engagement initiator
            'e_inbound_u_id' => 1, //Engagement recipient

            'x_inbound_u_id' => 5, //URL Creator
            'x_outbound_u_id' => 8, //URL Referenced to them

            'ru_outbound_u_id' => 13, //Subscriptions
            'c_inbound_u_id' => 21, //Active Intents
            't_inbound_u_id' => 55, //Transactions
            'ba_outbound_u_id' => 233, //Bootcamp team member
        );

        //Fetch child entities:
        $entities = $this->Db_model->u_fetch(array(
            'u_inbound_u_id' => ( count($u)>0 ? $u['u_id'] : 0 ),
        ));

        //Recursively loops through child entities:
        $score = 0;
        foreach($entities as $u_child){
            //Addup all child sores:
            $score += $this->update_u_e_score($u_child);
        }

        //Anything to update?
        if(count($u)>0){

            //Update this row:
            $score += count($entities) * $score_weights['u_inbound_u_id'];

            $score += count($this->Db_model->e_fetch(array(
                    'e_outbound_u_id' => $u['u_id'],
                ), 5000)) * $score_weights['e_outbound_u_id'];
            $score += count($this->Db_model->e_fetch(array(
                    'e_inbound_u_id' => $u['u_id'],
                ), 5000)) * $score_weights['e_inbound_u_id'];

            $score += count($this->Db_model->x_fetch(array(
                    'x_status >' => 0,
                    'x_outbound_u_id' => $u['u_id'],
                ))) * $score_weights['x_outbound_u_id'];
            $score += count($this->Db_model->x_fetch(array(
                    'x_status >' => 0,
                    'x_inbound_u_id' => $u['u_id'],
                ))) * $score_weights['x_inbound_u_id'];

            $score += count($this->Db_model->c_fetch(array(
                    'c_inbound_u_id' => $u['u_id'],
                ))) * $score_weights['c_inbound_u_id'];
            $score += count($this->Db_model->ru_fetch(array(
                    'ru_outbound_u_id' => $u['u_id'],
                ))) * $score_weights['ru_outbound_u_id'];
            $score += count($this->Db_model->ba_fetch(array(
                    'ba_outbound_u_id' => $u['u_id'],
                ))) * $score_weights['ba_outbound_u_id'];
            $score += count($this->Db_model->t_fetch(array(
                    't_inbound_u_id' => $u['u_id'],
                ))) * $score_weights['t_inbound_u_id'];

            //Update the score:
            $this->Db_model->u_update( $u['u_id'] , array(
                'u_e_score' => $score,
            ));

            //return the score:
            return $score;

        }
    }




    /* ******************************
     * Messaging
     ****************************** */

    function message_drip(){

        //Cron Settings: */5 * * * *

        //Fetch pending drips
        $e_pending = $this->Db_model->e_fetch(array(
            'e_status' => 0, //Pending
            'e_inbound_c_id' => 52, //Scheduled Drip e_inbound_c_id=52
            'e_timestamp <=' => date("Y-m-d H:i:s" ), //Message is due
            //Some standard checks to make sure, these should all be true:
            'e_r_id >' => 0,
            'e_outbound_u_id >' => 0,
            'e_b_id >' => 0,
            'e_outbound_c_id >' => 0,
        ), 200, array('ej'));


        //Lock item so other Cron jobs don't pick this up:
        lock_cron_for_processing($e_pending);


        $drip_sent = 0;
        foreach($e_pending as $e_text_value){

            //Fetch user data:
            $matching_enrollments = $this->Db_model->ru_fetch(array(
                'ru_outbound_u_id' => $e_text_value['e_outbound_u_id'],
                'ru_r_id' => $e_text_value['e_r_id'],
                'ru_status >=' => 4, //Active student
                'r_status' => 2, //Running Class
            ));

            if(count($matching_enrollments)>0){

                //Prepare variables:
                $json_data = unserialize($e_text_value['ej_e_blob']);

                //Send this message:
                $this->Comm_model->send_message(array(
                    array_merge($json_data['i'], array(
                        'e_inbound_u_id' => 0,
                        'e_outbound_u_id' => $matching_enrollments[0]['u_id'],
                        'i_outbound_c_id' => $json_data['i']['i_outbound_c_id'],
                        'e_b_id' => $e_text_value['e_b_id'],
                        'e_r_id' => $e_text_value['e_r_id'],
                    )),
                ));

                //Update Engagement:
                $this->Db_model->e_update( $e_text_value['e_id'] , array(
                    'e_status' => 1, //Mark as done
                ));

                //Increase counter:
                $drip_sent++;
            }
        }

        //Echo message for cron job:
        echo $drip_sent.' Drip messages sent';

    }


    function save_profile_pic(){


        $max_per_batch = 20; //Max number of scans per run

        $e_pending = $this->Db_model->e_fetch(array(
            'e_status' => 0, //Pending
            'e_inbound_c_id' => 7001, //Cover Photo Save
        ), $max_per_batch);


        //Lock item so other Cron jobs don't pick this up:
        lock_cron_for_processing($e_pending);


        $counter = 0;
        foreach($e_pending as $u){

            //Check URL and validate:
            $error_message = null;
            $curl = curl_html($u['e_text_value'],true);

            if(!$curl){
                $error_message = 'Invalid URL (start with http:// or https://)';
            } elseif($curl['url_is_broken']) {
                $error_message = 'URL Seems broken with http code ['.$curl['httpcode'].']';
            } elseif($curl['x_type']!=4) {
                $error_message = 'URL [Type '.$curl['x_type'].'] Does not point to an image';
            }

            if(!$error_message){

                //Save the file to S3
                $new_file_url = save_file($u['e_text_value'],$u);

                if(!$new_file_url){
                    $error_message = 'Failed to upload the file to Mench CDN';
                }

                //Check to make sure this is not a Generic FB URL:
                foreach(array(
                            'ecd274930db69ba4b2d9137949026300',
                            '5bf2d884209d168608b02f3d0850210d',
                            'b3575aa3d0a67fb7d7a076198b442b93',
                            'e35cf96f814f6509d8a202efbda18d3c',
                            '5d2524cb2bdd09422832fa2d25399049',
                            '164c8275278f05c770418258313fb4f4',
                            '',
                        ) as $generic_url){
                    if(substr_count($new_file_url,$generic_url)>0){
                        //This is the hashkey for the Facebook Generic User icon:
                        $error_message = 'This is the user generic icon on Facebook';
                        break;
                    }
                }

                if(!$error_message){

                    //Save URL:
                    $new_x = $this->Db_model->x_create(array(
                        'x_inbound_u_id' => $u['u_id'],
                        'x_outbound_u_id' => $u['u_id'],
                        'x_url' => $new_file_url,
                        'x_clean_url' => $new_file_url,
                        'x_type' => 4, //Image
                    ));

                    //Replace cover photo only if this user has no cover photo set:
                    if(!(intval($u['u_cover_x_id'])>0)){

                        //Update Cover ID:
                        $this->Db_model->u_update( $u['u_id'] , array(
                            'u_cover_x_id' => $new_x['x_id'],
                        ));

                        //Log engagement:
                        $this->Db_model->e_create(array(
                            'e_inbound_u_id' => $u['u_id'],
                            'e_outbound_u_id' => $u['u_id'],
                            'e_inbound_c_id' => 12, //Account Update
                            'e_text_value' => 'Profile cover photo updates from Facebook Image ['.$u['e_text_value'].'] to Mench CDN ['.$new_file_url.']',
                            'e_x_id' => $new_x['x_id'],
                        ));
                    }
                }
            }

            //Update engagement:
            $this->Db_model->e_update( $u['e_id'] , array(
                'e_text_value' => ( $error_message ? 'ERROR: '.$error_message : 'Success' ).' (Original Image URL: '.$u['e_text_value'].')',
                'e_status' => 1, //Done
            ));

        }

        echo_json($e_pending);
    }

    function message_file_save(){

        //Cron Settings: * * * * *

        /*
         * This cron job looks for all engagements with Facebook attachments
         * that are pending upload (i.e. e_status=0) and uploads their
         * attachments to amazon S3 and then changes status to e_status=1
         *
         */

        $max_per_batch = 10; //Max number of scans per run

        $e_pending = $this->Db_model->e_fetch(array(
            'e_status' => 0, //Pending file upload to S3
            'e_inbound_c_id >=' => 6, //Messages only
            'e_inbound_c_id <=' => 7, //Messages only
        ), $max_per_batch, array('ej'));


        //Lock item so other Cron jobs don't pick this up:
        lock_cron_for_processing($e_pending);


        $counter = 0;
        foreach($e_pending as $ep){

            //Prepare variables:
            $json_data = unserialize($ep['ej_e_blob']);

            //Loop through entries:
            if(is_array($json_data) && isset($json_data['entry']) && count($json_data['entry'])>0){
                foreach($json_data['entry'] as $entry) {
                    //loop though the messages:
                    foreach($entry['messaging'] as $im){
                        //This should only be a message
                        if(isset($im['message'])) {
                            //This should be here
                            if(isset($im['message']['attachments'])){
                                //We should have attachments:
                                foreach($im['message']['attachments'] as $att){
                                    //This one too! It should be one of these:
                                    if(in_array($att['type'],array('image','audio','video','file'))){

                                        //Store to local DB:
                                        $new_file_url = save_file($att['payload']['url'],$json_data);

                                        //Update engagement data:
                                        $this->Db_model->e_update( $ep['e_id'] , array(
                                            'e_text_value' => ( strlen($ep['e_text_value'])>0 ? $ep['e_text_value']."\n\n" : '' ).'/attach '.$att['type'].':'.$new_file_url, //Makes the file preview available on the message
                                            'e_status' => 1, //Mark as done
                                        ));

                                        //Increase counter:
                                        $counter++;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                //This should not happen, report:
                $this->Db_model->e_create(array(
                    'e_inbound_u_id' => 0, //System
                    'e_text_value' => 'cron/bot_save_files() fetched ej_e_blob() that was missing its [entry] value',
                    'e_json' => $json_data,
                    'e_inbound_c_id' => 8, //System Error
                ));
            }

            if($counter>=$max_per_batch){
                break; //done for now
            }
        }
        //Echo message for cron job:
        echo $counter.' Incoming Messenger file'.($counter==1?'':'s').' saved to Mench cloud.';
    }

    function message_fb_sync_attachments(){

        //Cron Settings: * * * * *

        /*
         * This cron job looks for all requests to sync Message attachments
         * with Facebook, gets them done and marks the engagement as done
         *
         */

        $success_count = 0; //Track success
        $max_per_batch = 55; //Max number of syncs per cron run
        $e_json = array();

        $e_pending = $this->Db_model->e_fetch(array(
            'e_status' => 0, //Pending Sync
            'e_inbound_c_id' => 83, //Message Facebook Sync e_inbound_c_id=83
        ), $max_per_batch, array('i','fp'));


        //Lock item so other Cron jobs don't pick this up:
        lock_cron_for_processing($e_pending);


        if(count($e_pending)>0){
            foreach($e_pending as $ep){

                //Does this meet the basic tests? It should...
                if($ep['fp_id']>0 && $ep['i_id']>0 && strlen($ep['i_url'])>0 && filter_var($ep['i_url'], FILTER_VALIDATE_URL) && in_array($ep['i_media_type'],array('video','image','audio','file'))){

                    //First make sure we don't already have this saved in v5_message_fb_sync already
                    $synced_messages = $this->Db_model->sy_fetch(array(
                        'sy_i_id' => $ep['i_id'],
                        'sy_fp_id' => $ep['fp_id'],
                    ));

                    if(count($synced_messages)==0){

                        $payload = array(
                            'message' => array(
                                'attachment' => array(
                                    'type' => $ep['i_media_type'],
                                    'payload' => array(
                                        'is_reusable' => true,
                                        'url' => $ep['i_url'],
                                    ),
                                ),
                            )
                        );

                        //Attempt to save this:
                        $result = $this->Comm_model->fb_graph($ep['fp_id'], 'POST', '/me/message_attachments', $payload);
                        $db_result = false;

                        if($result['status'] && isset($result['e_json']['result']['attachment_id'])){
                            //Save attachment to DB:
                            $db_result = $this->Db_model->sy_create(array(
                                'sy_i_id' => $ep['i_id'],
                                'sy_fp_id' => $ep['fp_id'],
                                'sy_fb_att_id' => $result['e_json']['result']['attachment_id'],
                            ));
                        }

                        //Did it go well?
                        if(is_array($db_result) && count($db_result)>0){
                            $success_count++;
                        } else {
                            //Log error:
                            $this->Db_model->e_create(array(
                                'e_text_value' => 'message_fb_sync_attachments() Failed to sync attachment using Facebook API',
                                'e_json' => array(
                                    'payload' => $payload,
                                    'result' => $result,
                                    'ep' => $ep,
                                ),
                                'e_inbound_c_id' => 8, //Platform Error
                            ));
                        }


                        //Update engagement:
                        $this->Db_model->e_update( $ep['e_id'], array(
                            'e_status' => 1, //Completed
                        ));


                        //Save stats either way:
                        array_push($e_json,array(
                            'payload' => $payload,
                            'fb_result' => $result,
                            'db_result' => $db_result,
                        ));

                    }
                }
            }
        }

        //Echo message for cron job:
        echo_json(array(
            'status' => ( $success_count==count($e_pending) && $success_count>0 ? 1 : 0 ),
            'message' => $success_count.'/'.count($e_pending).' Message'.echo__s(count($e_pending)).' successfully synced their attachment with Facebook',
            'e_json' => $e_json,
        ));

    }

    /* ******************************
	 * Coach
	 ****************************** */

    function coach_notify_student_activity(){

        //Cron Settings: 0 */2 * * * *
        //Runs every hour and informs coaches/admins of new messages received recently
        //Define settings:
        $seconds_ago = 7200; //Defines how much to go back, should be equal to cron job frequency

        //Create query:
        $after_time = date("Y-m-d H:i:s",(time()-$seconds_ago));


        //Fetch student inbound messages that have not yet been replied to:
        //TODO this could cause an issue if an coach takes a Class and tries to communicate... Fix later...
        $q = $this->db->query('SELECT u_full_name, e_inbound_u_id, COUNT(e_id) as received_messages FROM v5_engagements e JOIN v5_entities u ON (e.e_inbound_u_id = u.u_id) WHERE e_inbound_c_id=6 AND e_timestamp > \''.$after_time.'\' AND e_inbound_u_id>0 AND u_inbound_u_id NOT IN (1280,1308,1281) GROUP BY e_inbound_u_id, u_inbound_u_id, u_full_name');
        $new_messages = $q->result_array();
        $notify_messages = array();
        foreach($new_messages as $key=>$nm){

            //Lets see if their inbound messages has been responded by the coach:
            $messages = $this->Db_model->e_fetch(array(
                'e_inbound_c_id IN (6,7)' => null,
                'e_timestamp >' => $after_time,
                '(e_inbound_u_id='.$nm['e_inbound_u_id'].' OR e_outbound_u_id='.$nm['e_inbound_u_id'].')' => null,
            ));

            if(count($messages)>$nm['received_messages']){
                //We also sent some messages, see who sent them, and if we need to notify the admin:
                $last_message = $messages[0]; //This is the latest message
                $new_messages[$key]['notify'] = ( $last_message['e_inbound_c_id']==7 && $last_message['e_inbound_u_id']>0 ? 0 : 1 );
            } else {
                //No responses, we must notify:
                $new_messages[$key]['notify'] = 1;
            }


            if($new_messages[$key]['notify']){

                //Lets see who is responsible for this student:
                //Checks to see who is responsible for this user, likely to receive update messages or something...
                $enrollments = $this->Db_model->remix_enrollments(array(
                    'ru_outbound_u_id'	     => $nm['e_inbound_u_id'],
                    'ru_status >='	 => 0,
                ));
                $active_enrollment = detect_active_enrollment($enrollments); //We'd need to see which enrollment to load now

                if($active_enrollment && $active_enrollment['ru_upfront_pay']>0 /* Coaching Students Only */){

                    unset($notify_fb_ids);
                    $notify_fb_ids = array();
                    $b_data = array(
                        'b_id' => $active_enrollment['b_id'],
                        'c_outcome' => $active_enrollment['c_outcome'],
                    );
                    //Fetch the admins for this enrollment:
                    foreach($active_enrollment['b__coaches'] as $admin){
                        //We can handle either email or messenger connection:
                        array_push( $notify_fb_ids , array(
                            'u_full_name' => $admin['u_full_name'],
                            'u_id' => $admin['u_id'],
                        ));
                    }

                    if(count($notify_fb_ids)>0){

                        //Group these messages based on their receivers:
                        $md5_key = substr(md5(print_r($b_data,true)),0,8).substr(md5(print_r($notify_fb_ids,true)),0,8);
                        if(!isset($notify_messages[$md5_key])){
                            $notify_messages[$md5_key] = array(
                                'notify_admins' => $notify_fb_ids,
                                'b_data' => $b_data,
                                'message_threads' => array(),
                            );
                        }

                        array_push($notify_messages[$md5_key]['message_threads'] , $new_messages[$key]);

                    }
                }
            }
        }


        //Now see if we need to notify any admin:
        if(count($notify_messages)>0){
            foreach($notify_messages as $key=>$msg){

                //Prepare the message Body:
                $message = null;
                if(count($msg['b_data'])>0){
                    $message .= '🎯 '.$msg['b_data']['c_outcome']."\n";
                }
                $message .= '💡 Coaching Student activity in the past '.round($seconds_ago/3600).' hours:'."\n";
                foreach($msg['message_threads'] as $thread){
                    $message .= "\n".$thread['received_messages'].' message'.echo__s($thread['received_messages']).' from '.$thread['u_full_name'];
                }
                if(count($msg['b_data'])>0 && strlen($message)<580){
                    $message .= "\n\n".'https://mench.com/console/'.$msg['b_data']['b_id'];
                }

                $notify_messages[$key]['admin_message'] = $message;

                //Send message to all admins:
                foreach($msg['notify_admins'] as $admin){

                    $this->Comm_model->send_message(array(
                        array(
                            'i_media_type' => 'text',
                            'i_message' => substr($message,0,620), //Make sure this is not too long!
                            'e_inbound_u_id' => 0, //System
                            'e_outbound_u_id' => $admin['u_id'],
                            'e_b_id' => ( isset($msg['b_data']['b_id']) ? $msg['b_data']['b_id'] : 0),
                        ),
                    ));

                }
            }
        }

        echo_json($notify_messages);
    }

    /* ******************************
	 * Students
	 ****************************** */

    function student_reminder_complete_application(){

        //Cron Settings: 10 * * * *

        //Fetch current incomplete applications:
        $incomplete_applications = $this->Db_model->ru_fetch(array(
            'r.r_status'	=> 1, //Open For Subscription
            'ru.ru_status'  => 0,
        ));


        $stats = array();
        foreach($incomplete_applications as $enrollment){

            //Fetch existing reminders sent to this student:
            $reminders_sent = $this->Db_model->e_fetch(array(
                'e_inbound_c_id IN (7,28)' => null, //Email/Message sent
                'e_outbound_u_id' => $enrollment['u_id'],
                'e_r_id' => $enrollment['r_id'],
                'e_outbound_c_id IN (3140,3127)' => null, //The ID of the 5 email reminders https://mench.com/console/53/actionplan
            ));

            $enrollment_end_time = strtotime($enrollment['r_start_date']) - 60; //11:59PM the night before start date
            $enrollment_time = strtotime($enrollment['ru_timestamp']);


            //Send them a reminder to complete 24 hours after they start, only IF they started their application more than 6 days before the Class start:
            $reminder_c_id = 0;
            if(($enrollment_time+(3*24*3600))<$enrollment_end_time && ($enrollment_time+(24*3600))<time() && !filter($reminders_sent,'e_outbound_c_id',3127)){
                //Sent 24 hours after initiating enrollment IF registered more than 3 days before Class starts
                $reminder_c_id = 3127;
            } elseif(($enrollment_time+(26*3600))<$enrollment_end_time && (time()+(24*3600))>$enrollment_end_time && !filter($reminders_sent,'e_outbound_c_id',3140)){
                //Sent 24 hours before class starts IF registered more than 26 hours before Class starts
                $reminder_c_id = 3140;
            }

            if($reminder_c_id){
                //Send reminder:
                $this->Comm_model->foundation_message(array(
                    'e_inbound_u_id' => 0,
                    'e_outbound_u_id' => $enrollment['u_id'],
                    'e_outbound_c_id' => $reminder_c_id,
                    'depth' => 0,
                    'e_b_id' => $enrollment['ru_b_id'],
                    'e_r_id' => $enrollment['r_id'],
                ));

                //Push stats:
                array_push($stats, array(
                    'email' => $reminder_c_id,
                    'ru_id' => $enrollment['ru_id'],
                    'r_id' => $enrollment['r_id'],
                    'u_id' => $enrollment['u_id'],
                    'ru_timestamp' => $enrollment['ru_timestamp'],
                    'r_start_date' => $enrollment['r_start_date'],
                    'reminders' => $reminders_sent,
                ));
            }
        }

        echo_json($stats);
    }

    function student_reminder_complete_task(){

        //Cron Settings: 45 * * * *
        //Send reminders to students to complete their Steps:

        $enrollments = $this->Db_model->ru_fetch(array(
            'r.r_status'	    => 2, //Running Class
            'ru.ru_status'      => 4, //Enrolled Students
        ));

        //Define the logic of these reminders
        $reminder_index = array(
            array(
                'time_elapsed'   => 0.90,
                'progress_below' => 1.00,
                'reminder_c_id'  => 3139,
            ),
            array(
                'time_elapsed'   => 0.75,
                'progress_below' => 0.50,
                'reminder_c_id'  => 3138,
            ),
            array(
                'time_elapsed'   => 0.50,
                'progress_below' => 0.25,
                'reminder_c_id'  => 3137,
            ),
            array(
                'time_elapsed'   => 0.25,
                'progress_below' => 0.10,
                'reminder_c_id'  => 3136,
            ),
            array(
                'time_elapsed'   => 0.10,
                'progress_below' => 0.01,
                'reminder_c_id'  => 3358,
            ),
        );

        $stats = array();
        foreach($enrollments as $enrollment){

            //Fetch full Bootcamp/Class data for this:
            $bs = fetch_action_plan_copy($enrollment['ru_b_id'], $enrollment['r_id']);
            $class = $bs[0]['this_class'];

            //See what % of the class time has elapsed?
            $elapsed_class_percentage = round((time()-strtotime($class['r_start_date']))/(class_ends($bs[0], $class)-strtotime($class['r_start_date'])),5);

            foreach ($reminder_index as $logic){
                if($elapsed_class_percentage>=$logic['time_elapsed']){

                    if($enrollment['ru_cache__completion_rate']<$logic['progress_below']){

                        //See if we have reminded them already about this:
                        $reminders_sent = $this->Db_model->e_fetch(array(
                            'e_inbound_c_id IN (7,28)' => null, //Email or Message sent
                            'e_outbound_u_id' => $enrollment['u_id'],
                            'e_r_id' => $enrollment['r_id'],
                            'e_outbound_c_id' => $logic['reminder_c_id'],
                        ));

                        if(count($reminders_sent)==0){

                            //Nope, send this message out:
                            $this->Comm_model->foundation_message(array(
                                'e_inbound_u_id' => 0, //System
                                'e_outbound_u_id' => $enrollment['u_id'],
                                'e_outbound_c_id' => $logic['reminder_c_id'],
                                'depth' => 0,
                                'e_b_id' => $enrollment['ru_b_id'],
                                'e_r_id' => $enrollment['r_id'],
                            ));

                            //Show in stats:
                            array_push($stats,$enrollment['u_full_name'].' done '.round($enrollment['ru_cache__completion_rate']*100).'% (less than target '.round($logic['progress_below']*100).'%) where class is '.round($elapsed_class_percentage*100).'% complete and got reminded via c_id '.$logic['reminder_c_id']);
                        }
                    }

                    //Do not go further down the reminder types:
                    break;
                }
            }
        }

        echo_json($stats);
    }

}