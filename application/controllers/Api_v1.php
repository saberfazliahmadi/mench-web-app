<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api_v1 extends CI_Controller {
	
    /*
     * A hub of external micro apps transmitting data with the Mench server
     * 
     * */
	
    function __construct() {
		parent::__construct();
		
		$this->output->enable_profiler(FALSE);
	}

    function ping(){
        echo_json(array('status'=>'success'));
    }
	
	function index(){
		die('nothing here...');
	}
	
	/* ******************************
	 * Miscs
	 ****************************** */

	function load_menu($c_id,$hash_key){

        if(!isset($_POST['c_id']) || !isset($_POST['hash_key']) || !($_POST['hash_key']==md5($_POST['c_id'].'menu89Hash'))){
            die('<div class="alert alert-danger" role="alert">Invalid Project ID.</div>');
        }

        //Fetch projects:
        $bs = $this->Db_model->remix_projects(array(
            'b.b_status >=' => 4,
        ));

	    echo '<div class="row">';
        foreach($bs as $count=>$b){

            //Fetch class:
            $focus_class = filter_class($b['c__classes'],null);

            if(!$focus_class){
                continue;
            }

            echo '<div class="col-sm-6">
    <div class="card card-product">
        <!-- <div class="card-image"></div> -->

        <div class="card-content">';

            //echo '<h6 class="category text-muted">'.$b['ct_icon'].' '.$b['ct_name'].'</h6>';
            echo '<h4 class="card-title" style="font-size: 1.4em; line-height: 110%; margin:15px 0 12px 0;"><a href="/'.$b['b_url_key'].'">'.$b['c_objective'].'</a></h4>';
            echo '<div class="card-description"><b>'.$b['c__tasks_count'].' Task'.show_s($b['c__tasks_count']).': '.echo_hours(($b['c__estimated_hours']/7)).' per Day</b></div>';


            echo '<div class="card-description">By ';
            //Print lead admin:
            foreach($b['b__admins'] as $admin){
                if($admin['ba_status']==3){
                    echo '<span style="display:inline-block;"><img src="'.$admin['u_image_url'].'" /> '.$admin['u_fname'].' '.$admin['u_lname'].'</span>';
                }
            }
            echo '</div>';

            echo '<div class="footer">
                <div class="price">
                    <h4>'.echo_price($focus_class['r_usd_price']).'</h4>
                </div>
                <div class="stats"><span>Starts <b>'.time_format($focus_class['r_start_date'],1).'</b></span></div>
            </div>';

            echo '</div>
</div>
</div>';

            if(fmod($count,3)==0){
                echo '</div><div class="row">';
            }
        }
        echo '</div>';
    }

	function import_content_loader(){

        if(!isset($_POST['import_from_b_id']) || intval($_POST['import_from_b_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project source.',
            ));
            exit;
        }

        $udata = auth(2,0,$_POST['import_from_b_id']);
        if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Session expired. Login to try again.',
            ));
            exit;
        }

        $bs = $this->Db_model->remix_projects(array(
            'b_id' => $_POST['import_from_b_id'],
        ));
        if(!(count($bs)==1)){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Project source.',
            ));
            exit;
        }

        $import_items = array(
            //Action Plan stuff:
            array(
                'is_header' => 1,
                'name' => '<h4><i class="fa fa-dot-circle-o" aria-hidden="true"></i> '.$bs[0]['c_objective'].'</h4>',
            ),
            array(
                'is_header' => 0,
                'name' => '<i class="fa fa-commenting" aria-hidden="true"></i> Import Project-Level Messages',
                'id' => 'b_level_messages',
                'count' => count($bs[0]['c__messages']),
            ),

            array(
                'is_header' => 1,
                'name' => '<h5><i class="fa fa-sign-in" aria-hidden="true"></i> Screening</h5>',
            ),
            array(
                'is_header' => 0,
                'name' => '<i class="fa fa-address-book" aria-hidden="true"></i> Override Target Audience',
                'id' => 'b_target_audience',
                'count' => ( strlen($bs[0]['b_target_audience'])>0 ? count(json_decode($bs[0]['b_target_audience'])) : 0 ),
            ),
            array(
                'is_header' => 0,
                'name' => '<i class="fa fa-check-square-o" aria-hidden="true"></i> Override Prerequisites',
                'id' => 'b_prerequisites',
                'count' => ( strlen($bs[0]['b_prerequisites'])>0 ? count(json_decode($bs[0]['b_prerequisites'])) : 0 ),
            ),

            array(
                'is_header' => 1,
                'name' => '<h5><i class="fa fa-check-square-o" aria-hidden="true"></i> Tasks</h5>',
            ),
            array(
                'is_header' => 0,
                'name' => '<i class="fa fa-check-square-o" aria-hidden="true"></i> Import Published Tasks with all Steps & Messages',
                'id' => 'b_published_tasks',
                'count' => count($bs[0]['c__active_intents']),
            ),
            array(
                'is_header' => 0,
                'name' => '<i class="fa fa-check-square-o" aria-hidden="true"></i> Import Drafting Tasks with all Steps & Messages',
                'id' => 'b_drafting_tasks',
                'count' => (count($bs[0]['c__child_intents'])-count($bs[0]['c__active_intents'])),
            ),


            array(
                'is_header' => 1,
                'name' => '<h5><i class="fa fa-sign-out" aria-hidden="true"></i> Outcomes</h5>',
            ),
            array(
                'is_header' => 0,
                'name' => '<i class="fa fa-diamond" aria-hidden="true"></i> Override Skills You Will Gain',
                'id' => 'b_transformations',
                'count' => ( strlen($bs[0]['b_transformations'])>0 ? count(json_decode($bs[0]['b_transformations'])) : 0 ),
            ),
            array(
                'is_header' => 0,
                'name' => '<i class="fa fa-trophy" aria-hidden="true"></i> Override Completion Awards',
                'id' => 'b_completion_prizes',
                'count' => ( strlen($bs[0]['b_completion_prizes'])>0 ? count(json_decode($bs[0]['b_completion_prizes'])) : 0 ),
            ),

        );



        //Generate UI:
        $ui = '<p>Choose what to import:</p>'; //Start generating this...
        foreach($import_items as $item){
            if($item['is_header']){
                $ui .= '<div class="title">'.$item['name'].'</div>';
            } else {
                $ui .= '<div class="form-group label-floating is-empty"><div class="checkbox"><label><input type="checkbox" name="'.$item['id'].'" '.( $item['count']==0 ? 'disabled':'').' /> '.$item['count'].'x &nbsp;'.$item['name'].'</label></div></div>';
            }
        }


        echo_json(array(
            'status' => 1,
            'message' => null, //Not used, would load ui instead
            'ui' => $ui,
        ));
    }

    function import_process(){

        if(!isset($_POST['import_from_b_id']) || intval($_POST['import_from_b_id'])<=0 || !isset($_POST['import_to_b_id']) || intval($_POST['import_to_b_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project source.',
            ));
            exit;
        } elseif(!isset($_POST['task_import_mode']) || intval($_POST['task_import_mode'])<1 || intval($_POST['task_import_mode'])>3){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Task Import Mode',
            ));
            exit;
        } elseif(!isset($_POST['b_published_tasks']) || !isset($_POST['b_drafting_tasks'])){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Task Settings',
            ));
            exit;
        }

        $udata = auth(2,0,$_POST['import_from_b_id']);
        $udata2 = auth(2,0,$_POST['import_to_b_id']);
        if(!$udata || !$udata2){
            echo_json(array(
                'status' => 0,
                'message' => 'Session expired. Login to try again.',
            ));
            exit;
        }

        $bs_from = $this->Db_model->remix_projects(array(
            'b_id' => $_POST['import_from_b_id'],
        ));
        $bs_to = $this->Db_model->b_fetch(array(
            'b_id' => $_POST['import_to_b_id'],
        ));
        if(!(count($bs_from)==1)){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Project from.',
            ));
            exit;
        } elseif(!(count($bs_to)==1)){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Project to.',
            ));
            exit;
        }


        //We're good at this point
        //Start copying potential lists first:
        $lists = array(
            'b_target_audience',
            'b_prerequisites',
            'b_transformations',
            'b_completion_prizes',
        );
        $b_lists = array();
        foreach($lists as $list){
            //Do we need this list?
            if(isset($_POST[$list]) && intval($_POST[$list])){
                //Yes, copy this guy:
                $b_lists[$list] = $bs_from[0][$list];
            }
        }
        if(count($b_lists)>0){
            //Copy to new Action Plan:
            $this->Db_model->b_update( $_POST['import_to_b_id'] , $b_lists);
        }



        //Do we need to copy the Project-level message?
        $b_level_messages_results = array();
        if(isset($_POST['b_level_messages']) && intval($_POST['b_level_messages']) && count($bs_from[0]['c__messages'])>0){
            //Yes, import messages:
            $b_level_messages_results = copy_messages($udata['u_id'],$bs_from[0]['c__messages'],$bs_to[0]['b_c_id']);
        }


        //Do we need to do any Tasks?
        $new_intents = array();
        if(intval($_POST['b_published_tasks']) || intval($_POST['b_drafting_tasks'])){
            //Yes, we have either Published or Drafting Task copy request:

            foreach($bs_from[0]['c__child_intents'] as $task){

                if($task['c_status']<0 || ($task['c_status']==0 && !intval($_POST['b_drafting_tasks'])) || ($task['c_status']==1 && !intval($_POST['b_published_tasks']))){
                    continue;
                }

                unset($new_miletone);
                $new_miletone = array();

                //What modality is this?
                if(intval($_POST['task_import_mode'])==3){

                    //Copy intent:
                    $new_miletone = copy_intent($udata['u_id'],$task,$bs_to[0]['b_c_id']);

                    if(count($task['c__messages'])>0){
                        //Copy messages:
                        $new_miletone['c__messages'] = copy_messages($udata['u_id'],$task['c__messages'],$new_miletone['c_id']);
                    }

                }



                //Go through the Steps:
                $new_miletone['c__child_intents'] = array();
                foreach($task['c__child_intents'] as $step){

                    if(intval($_POST['task_import_mode'])==3){

                        //Copy intent:
                        $new_step = copy_intent($udata['u_id'],$step,$new_miletone['c_id']);

                        if(count($step['c__messages'])>0) {
                            //Copy messages:
                            $new_step['c__messages'] = copy_messages($udata['u_id'], $step['c__messages'], $new_step['c_id']);
                        }

                        array_push( $new_miletone['c__child_intents'] , $new_step );

                    }

                }

                array_push($new_intents,$new_miletone);

            }
        }


        //Log Engagement:
        $this->Db_model->e_create(array(
            'e_initiator_u_id' => $udata['u_id'],
            'e_json' => array(
                'import_settings' => $_POST,
                'b_lists' => $b_lists,
                'b_level_messages_results' => $b_level_messages_results,
                'new_intents' => $new_intents,
            ),
            'e_type_id' => 75, //Action Plan Imported
            'e_b_id' => $bs_to[0]['b_id'],
        ));



        //Show message & redirect:
        echo_json(array(
            'status' => 1, //Success
            'message' => '<span><img src="/img/round_done.gif?time='.time().'" class="loader"  /></span><div>Reloading Action Plan...</div>',
        ));
    }



    function log_engagement(){
	    //Validate hash code:
        if(!isset($_POST['e_hash_time']) || !isset($_POST['e_hash_code']) || strlen($_POST['e_hash_time'])<5 || strlen($_POST['e_hash_code'])<5 || !(md5($_POST['e_hash_time'].'hashcod3')==$_POST['e_hash_code'])){

            echo_json(array(
                'status' => 0,
                'message' => 'invalid hash key',
            ));

        } else {

            //Remove hash data:
            unset($_POST['e_hash_time']);
            unset($_POST['e_hash_code']);

            //Log engagement:
            $new_e = $this->Db_model->e_create($_POST);

            //Show messages:
            if($new_e['e_id']>0){
                echo_json(array(
                    'status' => 1,
                    'message' => 'Logged Engagement #'.$new_e['e_id'],
                ));
            } else {
                echo_json(array(
                    'status' => 0,
                    'message' => 'Invalid data structure for engagement logging',
                ));
            }

        }
    }


    function page_redirect($fp_id,$fp_hash){

	    if(!(md5($fp_id.'pageLinkHash000')==$fp_hash)){
	        die('invalid key');
        }

        $fp_pages = $this->Db_model->fp_fetch(array(
            'fp_id' => $fp_id,
        ));
        if(count($fp_pages)<1){
            die('invalid ID');
        }

        //Go to the inbox app by Facebook
        header( 'Location: https://www.facebook.com/'.$fp_pages[0]['fp_fb_id'].'/inbox/' );
    }

    function fb_connect(){

	    //Responsible to connect and disconnect the Facebook pages when instructors explicitly request this:
        if(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){

            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project ID',
            ));
            return false;

        } elseif(!isset($_POST['new_b_fp_id']) || intval($_POST['new_b_fp_id'])<0){

            echo_json(array(
                'status' => 0,
                'message' => 'Missing New Facebook Page ID',
            ));
            return false;

        } elseif(!isset($_POST['current_b_fp_id']) || intval($_POST['current_b_fp_id'])<0){

            echo_json(array(
                'status' => 0,
                'message' => 'Missing Current Facebook Page ID',
            ));
            return false;

        }

        $udata = auth(2,0,$_POST['b_id']);
        $bs = $this->Db_model->b_fetch(array(
            'b_id' => $_POST['b_id'],
        ));

        if(!$udata){

            echo_json(array(
                'status' => 0,
                'message' => 'Session expired. Login to try again.',
            ));
            return false;

        } elseif(count($bs)<1){

            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Project ID',
            ));
            return false;

        }

        //Validate input Page IDs:
        if($_POST['current_b_fp_id']>0){
            $fp_pages = $this->Db_model->fp_fetch(array(
                'fp_id' => $_POST['current_b_fp_id'],
            ));
            if(count($fp_pages)<1){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Invalid Current Page ID',
                ));
                return false;
            }
        }
        if($_POST['new_b_fp_id']>0){
            $fp_pages = $this->Db_model->fp_fetch(array(
                'fp_id' => $_POST['new_b_fp_id'],
            ));
            if(count($fp_pages)<1){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Invalid New Page ID',
                ));
                return false;
            }
        }


        //Is this a disconnect only request?
        $connection_status = false;
        if($_POST['current_b_fp_id']>0 && $_POST['new_b_fp_id']==0){
            //Disconnect
            $connection_status = $this->Comm_model->fb_page_disconnect($udata['u_id'], $_POST['current_b_fp_id'], $_POST['b_id']);
        } elseif($_POST['new_b_fp_id']>0) {
            //Connect, this code handles the disconnect of an existing page if any other page is connected already:
            $connection_status = $this->Comm_model->fb_page_connect($udata['u_id'], $_POST['new_b_fp_id'], $_POST['b_id']);
        }


        //Return result:
        if($connection_status) {
            //All good, let them know:
            echo_json(array(
                'status' => 1,
                'message' => 'Success', //This is not shown...
            ));
        } else {
            //Ooops, some error here...
            echo_json(array(
                'status' => 0,
                'message' => 'Unknown error trying to process your request',
            ));
        }
    }

	function list_facebook_pages(){

        $udata = auth(2,0,$_POST['b_id']);
        if(!$udata){

            echo '<div class="alert alert-danger maxout" role="alert"><i class="fa fa-info-circle" aria-hidden="true"></i> Session expired. Login to try again.</div>';
            return false;

        } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){

            echo '<div class="alert alert-danger maxout" role="alert"><i class="fa fa-info-circle" aria-hidden="true"></i> Missing Project ID.</div>';
            return false;

        }

        //Validate Project and later check current b_fb_id
        $bs = $this->Db_model->b_fetch(array(
            'b_id' => intval($_POST['b_id']),
        ));
        if(count($bs)<1){
            echo '<div class="alert alert-danger maxout" role="alert"><i class="fa fa-info-circle" aria-hidden="true"></i> Invalid Project ID.</div>';
            return false;
        }


        //Make sure we have their Access Token via the JS call that was made earlier...
        if(!isset($_POST['login_response']['authResponse']['accessToken'])){
            //We have an issue as we cannot access the user's access token, let them know:
            echo '<div class="alert alert-danger maxout" role="alert"><i class="fa fa-info-circle" aria-hidden="true"></i> Cannot fetch your Facebook Access Token.</div>';
            return false;
        }


        //Index and organize their pages:
        $authorized_fp_ids = $this->Comm_model->fb_index_pages($udata['u_id'],$_POST['login_response']['authResponse']['accessToken'],$_POST['b_id']);

        if(!is_array($authorized_fp_ids)){

            //Unknown processing error, let them know about this:
            //This has already been logged via fb_index_pages()
            echo '<div class="alert alert-danger maxout" role="alert"><i class="fa fa-info-circle" aria-hidden="true"></i> Unknown error while trying to list your Facebook Pages.</div>';
            return false;

        }

        //Do we have a page connected, or not?
        if(intval($bs[0]['b_fp_id'])>0 && !in_array($bs[0]['b_fp_id'],$authorized_fp_ids)) {

            //Project connected to a page they do not control, fetch details and let them know:
            $no_control_pages = $this->Db_model->fp_fetch(array(
                'fp_id' => $bs[0]['b_fp_id']
            ));
            if(count($no_control_pages)>0){
                echo '<div class="alert alert-info maxout" role="alert"><i class="fa fa-plug" aria-hidden="true"></i> Currently connected to a Page you don\'t control: <a href="https://www.facebook.com/'.$no_control_pages[0]['fp_fb_id'].'">'.$no_control_pages[0]['fp_name'].'</a></div>';
            }

        } elseif(intval($bs[0]['b_fp_id'])==0){
            //Indicate to the user that they do not have a match:
            echo '<div class="alert alert-info maxout" role="alert"><i class="fa fa-info-circle" aria-hidden="true"></i> Connect to a Facebook Page to activate Mench</div>';
        }



        //Was any of their previously authorized pages revoked, handle this:
        $admin_lost_pages = $this->Comm_model->fb_detect_revoked($udata['u_id'],$authorized_fp_ids,$_POST['b_id']);
        if(count($admin_lost_pages)>0){
            //Let them know that they lost access to certain pages that is no longer associated with their account:
            echo '<div class="alert alert-info maxout" role="alert"><i class="fa fa-info-circle" aria-hidden="true"></i> You lost access to '.count($admin_lost_pages).' page'.show_s(count($admin_lost_pages)).' since the last time you logged into Facebook.</div>';
        }




        //List pages:
        $ready_pages = $this->Db_model->fp_fetch(array(
            'fs_u_id' => $udata['u_id'],
            'fs_status' => 1, //Have access
            'fp_status >=' => 0, //Available or Connected
        ));


        //List UI:
        $pages_list_ui = '<div class="list-group maxout">';
        if(count($ready_pages)>0){
            foreach($ready_pages as $page){

                //Fetch all other Projects this page is connected to:
                $other_projects = $this->Db_model->b_fetch(array(
                    'b.b_fp_id' => $page['fp_id'],
                    'b.b_id !=' => $_POST['b_id'],
                ),array('c'));
                
                $pages_list_ui .= '<li class="list-group-item">';

                //Right content
                if($page['fp_status']>=0){
                    $pages_list_ui .= '<span class="pull-right">';

                    if($page['fp_status']==1 && $udata['u_status']>=3){
                        $pages_list_ui .= '<a id="simulate_'.$page['fp_id'].'" class="badge badge-primary btn-mls" href="javascript:refresh_integration('.$page['fp_id'].')" data-toggle="tooltip" title="Refresh the Mench integration on your Facebook Page to resolve any possible connection issues." data-placement="left"><i class="fa fa-refresh" aria-hidden="true"></i></a>';
                    }

                    if($bs[0]['b_fp_id']>0 && $page['fp_id']==$bs[0]['b_fp_id']){
                        //This page is already assigned:
                        $pages_list_ui .= '<b><i class="fa fa-plug" aria-hidden="true"></i> Connected</b> &nbsp;';
                        $pages_list_ui .= '<a href="javascript:void(0);" onclick="fb_connect('.$bs[0]['b_fp_id'].',0)" class="badge badge-primary badge-msg" style="text-decoration:none; margin-top:-4px;"><i class="fa fa-times-circle" aria-hidden="true"></i> Disconnect</a>';
                    } else {
                        //Give the option to connect:
                        $pages_list_ui .= '<a href="javascript:void(0);" onclick="fb_connect('.$bs[0]['b_fp_id'].','.$page['fp_id'].')" class="badge badge-primary badge-msg" style="text-decoration:none; margin-top:-4px;"><i class="fa fa-plug" aria-hidden="true"></i> Connect</a>';
                    }
                    $pages_list_ui .= '</span> ';
                }

                //Left content
                $pages_list_ui .= status_bible('fp',$page['fp_status'],true, 'right');
                $pages_list_ui .= ' '.$page['fp_name'];
                $pages_list_ui .= ' <a href="https://www.facebook.com/'.$page['fp_fb_id'].'" target="_blank" style="font-size:0.9em;"><i class="fa fa-external-link-square" aria-hidden="true"></i></a>';


                if(count($other_projects)>0){
                    //Show other connected Projects:
                    $pages_list_ui .= '<div style="font-size:15px; padding:3px 0 0 4px;"><i class="fa fa-info-circle" aria-hidden="true"></i> &nbsp; Other Connections: ';
                    foreach($other_projects as $count=>$b){
                        if($count>0){
                            $pages_list_ui .= ', ';
                        }
                        $pages_list_ui .= '<a href="/console/'.$b['b_id'].'/settings#pages">'.$b['c_objective'].'</a>';
                    }
                    $pages_list_ui .= '</div>';
                }

                //Do we have a Page greeting?
                if(strlen($page['fp_greeting'])>0){
                    $pages_list_ui .= '<div style="font-size:15px; padding:3px 0 0 4px;">'.nl2br($page['fp_greeting']).'</div>';
                }

                $pages_list_ui .= '</li>';
            }
        } else {
            //No page found!
            $pages_list_ui .= '<li class="list-group-item" style="color:#FF0000;"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> No Facebook Pages found. Create a new one to continue...</li>';
        }

        //Link to create a new Facebook page:
        $pages_list_ui .= '<a href="https://www.facebook.com/pages/create" class="list-group-item"><i class="fa fa-plus-square" style="color:#fedd16;" aria-hidden="true"></i> Create New Facebook Page</a>';
        $pages_list_ui .= '</div>';

        //Show the UI:
        echo $pages_list_ui;

    }

    function blob($e_id){
        $udata = auth(3,1);
        //Fetch blob of engagement and display it on screen:
        $blobs = $this->Db_model->e_fetch(array(
            'ej_e_id' => $e_id,
        ),1,array('ej'));
        if(count($blobs)==1){
            echo_json(array(
                'blob' => unserialize($blobs[0]['ej_e_blob']),
                'e' => $blobs[0]
            ));
        } else {
            echo_json(array('error'=>'Not Found'));
        }
    }

	
	function load_tip(){
	    $udata = auth(2);
	    //Used to load all the help messages within the Console:
	    if(!$udata || !isset($_POST['intent_id']) || intval($_POST['intent_id'])<1){
	        echo_json(array(
	            'success' => 0,
	        ));
	    }
	    
	    //Fetch Messages and the User's Got It Engagement History:
	    $messages = $this->Db_model->i_fetch(array(
	        'i_c_id' => intval($_POST['intent_id']),
	        'i_status >' => 0, //Published in any form
	    ));
	    
	    //Log an engagement for all messages
	    foreach($messages as $i){
	        $this->Db_model->e_create(array(
	            'e_initiator_u_id' => $udata['u_id'],
	            'e_json' => $i,
	            'e_type_id' => 40, //Got It
	            'e_c_id' => intval($_POST['intent_id']),
	            'e_i_id' => $i['i_id'],
	        ));
	    }
	    
	    //Build UI friendly Message:
	    $help_content = null;
	    foreach($messages as $i){
	        $help_content .= echo_i(array_merge($i,array('e_recipient_u_id'=>$udata['u_id'])),$udata['u_fname']);
	    }
	    
	    //Return results:
	    echo_json(array(
	        'success' => ( $help_content ?  1 : 0 ), //No Messages perhaps!
	        'intent_id' => intval($_POST['intent_id']),
	        'help_content' => $help_content,
	    ));
	}
	
	/* ******************************
	 * Users
	 ****************************** */

	function update_review(){
        if(!isset($_POST['ru_id']) || !isset($_POST['ru_key']) || intval($_POST['ru_id'])<1 || !($_POST['ru_key']==substr(md5($_POST['ru_id'].'r3vi3wS@lt'),0,6))){
            die('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error: Invalid Admission Data.</div>');
        } elseif(!isset($_POST['ru_review_score']) || intval($_POST['ru_review_score'])<1 || intval($_POST['ru_review_score'])>10){
            die('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error: Review Score must be between 1-10.</div>');
        }

        //Validate Admission:
        $admissions = $this->Db_model->ru_fetch(array(
            'ru_id' => intval($_POST['ru_id']),
        ));
        if(count($admissions)<1){
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => 0, //System
                'e_message' => 'Validated review submission call failed to fetch admission data',
                'e_type_id' => 8, //System Error
            ));
            die('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error: Unable to locate your Admission.</div>');
        }

        //Is this a new review, or updating an existing one?
        $new_review = ( intval($admissions[0]['ru_review_score'])<0 );
        $has_text = ( strlen($_POST['ru_review_public_note'])>0 || strlen($_POST['ru_review_private_note'])>0 );
        $update_data = array(
            'ru_review_time' => date("Y-m-d H:i:s"),
            'ru_review_score' => $_POST['ru_review_score'],
            'ru_review_public_note' => $_POST['ru_review_public_note'],
            'ru_review_private_note' => $_POST['ru_review_private_note'],
        );

        //Save Engagement that is visible to instructor:
        $this->Db_model->e_create(array(
            'e_initiator_u_id' => $admissions[0]['u_id'],
            'e_message' => ( $new_review ? 'Student rated your Class ' : 'Student updated their rating for your Class to ' ).intval($_POST['ru_review_score']).'/10 with the following review: '.( strlen($_POST['ru_review_public_note'])>0 ? $_POST['ru_review_public_note'] : 'No Review' ),
            'e_json' => $update_data,
            'e_type_id' => 72, //Student Reviewed Class
            'e_b_id' => $admissions[0]['r_b_id'],
            'e_r_id' => $admissions[0]['r_id'],
        ));

        //Do they have a Private Feedback? Log a need attention Engagement to Mench team reads instantly:
        if(strlen($_POST['ru_review_private_note'])>0){
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $admissions[0]['u_id'],
                'e_message' => 'Received the following private/anonymous feedback: '.$_POST['ru_review_private_note'],
                'e_json' => $update_data,
                'e_type_id' => 9, //Support Needing Graceful Errors
                'e_b_id' => $admissions[0]['r_b_id'],
                'e_r_id' => $admissions[0]['r_id'],
            ));
        }

        //Update data:
        $this->Db_model->ru_update($admissions[0]['ru_id'], $update_data);

        //Show success and thank student:
        echo '<div class="alert alert-success">Thanks for '.($new_review?'submitting':'updating').' your review 👌'.( $has_text ? ' We read every single review and use your feedback to continuously improve 🙌​' : '' ).'</div>';

        //TODO Encourage sharing IF reviewed highly...

    }

    function request_password_reset(){
        //We need an email input:
        if(!isset($_POST['email'])){
            die('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error: Missing Email.</div>');
        } elseif(!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)){
            die('<div class="alert alert-danger"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error: Invalid Email.</div>');
        }

        //Attempt to fetch this user:
        $matching_users = $this->Db_model->u_fetch(array(
            'u_email' => strtolower($_POST['email']),
        ));
        if(count($matching_users)>0){
            //Dispatch the password reset node:
            $this->Comm_model->foundation_message(array(
                'e_initiator_u_id' => 0,
                'e_recipient_u_id' => $matching_users[0]['u_id'],
                'e_c_id' => 3030,
                'depth' => 0,
                'e_b_id' => 0,
                'e_r_id' => 0,
            ), true);
        }

        //Show message:
        echo '<div class="alert alert-success">Password reset accepted. You will receive an email only if you have a registered Mench account.</div>';
        echo '<script> $(document).ready(function() { $(".pass_success").hide(); }); </script>';

    }

    function update_new_password(){
        //This function updates the user's new password as requested via a password reset:
        if(!isset($_POST['u_id']) || intval($_POST['u_id'])<=0 || !isset($_POST['timestamp']) || intval($_POST['timestamp'])<=0 || !isset($_POST['p_hash']) || strlen($_POST['p_hash'])<10){
            echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error: Missing Core Variables.</div>';
        } elseif(!($_POST['p_hash']==md5($_POST['u_id'] . 'p@ssWordR3s3t' . $_POST['timestamp']))){
            echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error: Invalid hash key.</div>';
        } elseif(!isset($_POST['new_pass']) || strlen($_POST['new_pass'])<6){
            echo '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Error: New password must be longer than 6 characters. Try again.</div>';
        } else {
            //All seems good, lets update their account:
            $this->Db_model->u_update( intval($_POST['u_id']) , array(
                'u_password' => md5($_POST['new_pass']),
            ));

            //Log engagement:
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => intval($_POST['u_id']),
                'e_type_id' => 59, //Password reset
            ));

            //Log all sessions out:
            $this->session->sess_destroy();

            //Show message:
            echo '<div class="alert alert-success">Passsword reset successful. You can <a href="/login"><u>login here</u></a>.</div>';
            echo '<script> $(document).ready(function() { $(".pass_success").hide(); }); </script>';
        }
    }
	
	function funnel_progress(){
	    
	    $this->load->helper('cookie');
        $application_status_salt = $this->config->item('application_status_salt');

	    if(!isset($_POST['r_id']) || intval($_POST['r_id'])<1 || !isset($_POST['u_fname']) || !isset($_POST['u_email'])){
	        die(echo_json(array(
	            'status' => 0,
	            'error_message' => '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Missing Core Data</div>',
	        )));
	    }
	    
	    //Fetch inputs:
	    $classes = $this->Db_model->r_fetch(array(
	        'r.r_id' => intval($_POST['r_id']),
	        'r.r_status' => 1,
	    ));

	    if(count($classes)==1){
            $bs = $this->Db_model->remix_projects(array(
                'b.b_id' => $classes[0]['r_b_id'],
            ));
            $b = $bs[0];
            $focus_class = filter_class($b['c__classes'],intval($_POST['r_id']));
        }
	    
	    //Display results:
	    if(!isset($b) || !isset($classes[0]) || $b['b_id']<1 || !($focus_class['r_id']==intval($_POST['r_id']))) {

            die(echo_json(array(
                'status' => 0,
                'error_message' => '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Invalid Class ID</div>',
            )));

        } elseif(!isset($_POST['u_email']) || strlen($_POST['u_email'])<1 || !filter_var($_POST['u_email'], FILTER_VALIDATE_EMAIL)){

            die(echo_json(array(
                'status' => 0,
                'error_message' => '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Invalid email, try again.</div>',
            )));

        }

        //Fetch user data to see if already registered:
        $users = $this->Db_model->u_fetch(array(
            'u_email' => strtolower($_POST['u_email']),
        ));

        if(count($users)==0){

            if(!isset($_POST['u_fname']) || strlen($_POST['u_fname'])<2){
                //Invalid First name,
                die(echo_json(array(
                    'status' => 0,
                    'error_message' => '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> Invalid first name, try again.</div>',
                )));
            } else {

                //Create new user:
                $udata = $this->Db_model->u_create(array(
                    'u_status' 			=> 0, //Since nothing is yet validated
                    'u_language' 		=> 'en', //Since they answered initial questions in English
                    'u_email' 			=> trim(strtolower($_POST['u_email'])),
                    'u_fname' 			=> trim($_POST['u_fname']),
                ));

                //Log Engagement for registration:
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => $udata['u_id'], //The user that updated the account
                    'e_json' => array(
                        'input' => $_POST,
                        'udata' => $udata,
                    ),
                    'e_type_id' => 27, //New Student Lead
                    'e_b_id' => $b['b_id'],
                    'e_r_id' => $focus_class['r_id'],
                ));

            }

        } else {

            //This is a registered user!
            $udata = $users[0];

            //Make sure they have not enrolled in this Class before:
            $duplicate_registries = $this->Db_model->ru_fetch(array(
                'ru.ru_u_id'	   => $udata['u_id'],
                'ru.ru_r_id'	   => $focus_class['r_id'],
                'ru.ru_status >='  => 0,
            ));
            if(count($duplicate_registries)>0){

                //Send the email to their admission page:
                $this->Comm_model->foundation_message(array(
                    'e_initiator_u_id' => 0,
                    'e_recipient_u_id' => $udata['u_id'],
                    'e_c_id' => 2697,
                    'depth' => 0,
                    'e_b_id' => $duplicate_registries[0]['r_b_id'],
                    'e_r_id' => $duplicate_registries[0]['r_id'],
                ), true);

                if($duplicate_registries[0]['ru_status']==0){

                    $u_key = md5($udata['u_id'] . $application_status_salt);

                    //They still need to complete their application for this Class, redirect them to the next step:
                    //Logic is inspired from my_applications.php file
                    if(strlen($duplicate_registries[0]['ru_application_survey'])==0){
                        //Need to complete the Application:
                        die(echo_json(array(
                            'status' => 1,
                            'hard_redirect' => '/my/class_application/'.$duplicate_registries[0]['ru_id'].'?u_key='.$u_key.'&u_id='.$udata['u_id'],
                        )));
                    } elseif($duplicate_registries[0]['r_usd_price']>0){
                        //This must be the case if they have already completed the Application:
                        die(echo_json(array(
                            'status' => 0,
                            'error_message' => '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> You have already submitted your Application Questionnaire for this class. To complete your application, we emailed you a link to pay your price online. Check your email to continue.</div>',
                        )));
                    } else {
                        //This should not happen! Log Error:
                        $this->Db_model->e_create(array(
                            'e_initiator_u_id' => $udata['u_id'],
                            'e_message' => 'Student admission was incomplete, but they had already submitted their Questionnaire and the Class is Free!',
                            'e_json' => $duplicate_registries,
                            'e_type_id' => 8, //System Error
                            'e_b_id' => $b['b_id'],
                            'e_r_id' => $focus_class['r_id'],
                        ));
                    }

                } else {
                    //Show them an error:
                    die(echo_json(array(
                        'status' => 0,
                        'error_message' => '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> You have already enrolled in this class. Your current status is ['.trim(strip_tags(status_bible('ru',$duplicate_registries[0]['ru_status']))).']. '.($duplicate_registries[0]['ru_status']==-2 ? '<a href="/contact"><u>Contact us</u></a> if you like to restart your application for this class.' : 'We emailed you a link to manage your admissions. Check your email to continue.').'</div>',
                    )));
                }

            }


            //Check their current application status(es):
            $admissions = $this->Db_model->remix_admissions(array(
                'ru.ru_u_id'	   => $udata['u_id'],
                'ru.ru_r_id !='	   => $focus_class['r_id'],
                'r.r_status >='	   => 1, //Open for admission
                'r.r_status <='	   => 2, //Running
                'ru.ru_status'     => 4, //Admitted Student
            ));

            if(count($admissions)>0){

                //They are enrolled in another Class, let's see if the dates overlap:
                foreach($admissions as $admission){

                    if(($focus_class['r__class_start_time']>=$admission['r__class_start_time'] && $focus_class['r__class_start_time']<$admission['r__class_end_time']) || ($focus_class['r__class_end_time']>=$admission['r__class_start_time'] && $focus_class['r__class_end_time']<$admission['r__class_end_time'])){

                        //Send email for a link to their admission page:
                        $this->Comm_model->foundation_message(array(
                            'e_initiator_u_id' => 0,
                            'e_recipient_u_id' => $udata['u_id'],
                            'e_c_id' => 2697,
                            'depth' => 0,
                            'e_b_id' => $admission['r_b_id'],
                            'e_r_id' => $admission['r_id'],
                        ), true);


                        //Either start time or end time falls within this class!
                        $message = 'Admission blocked because you can join a maximum of 1 concurrent Projects. You are already enrolled in ['.$admission['c_objective'].'] that runs between ['.time_format($admission['r__class_start_time'],1).' - '.time_format($admission['r__class_end_time'],1).'].'."\n\n".'This overlaps with your request to join this Project ['.$b['c_objective'].'] that runs between ['.time_format($focus_class['r__class_start_time'],1).' - '.time_format($focus_class['r__class_end_time'],1).'].'."\n\n".'We emailed you a link to manage your current admissions.'.( $admission['ru_status']==0 ? ' You can choose to withdraw your application from ['.$admission['c_objective'].'] because your current application status is ['.trim(strip_tags(status_bible('ru',$admission['ru_status']))).'].' : '' );

                        //Log engagement:
                        $this->Db_model->e_create(array(
                            'e_initiator_u_id' => $udata['u_id'],
                            'e_message' => $message,
                            'e_json' => $_POST,
                            'e_type_id' => 9, //Support Needing Graceful Errors
                            'e_b_id' => $b['b_id'],
                            'e_r_id' => $focus_class['r_id'],
                        ));

                        //show the error:
                        die(echo_json(array(
                            'status' => 0,
                            'error_message' => '<div class="alert alert-danger" role="alert"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> '.nl2br($message).'</div>',
                        )));

                    }
                }
            }
        }

        //Lets start their admission application:
        $admissions[0] = $this->Db_model->ru_create(array(
            'ru_r_id' 	        => $focus_class['r_id'],
            'ru_u_id' 	        => $udata['u_id'],
            'ru_fp_id'          => $b['b_fp_id'], //Current Page that the student should connect to
        ));

        //Log engagement for Application Started:
        $this->Db_model->e_create(array(
            'e_initiator_u_id' => $udata['u_id'],
            'e_json' => array(
                'input' => $_POST,
                'udata' => $udata,
                'rudata' => $admissions[0],
            ),
            'e_type_id' => 29, //Application Started
            'e_b_id' => $b['b_id'],
            'e_r_id' => $focus_class['r_id'],
        ));

        //Send the email to their admission page:
        $this->Comm_model->foundation_message(array(
            'e_initiator_u_id' => 0,
            'e_recipient_u_id' => $udata['u_id'],
            'e_c_id' => 2697,
            'depth' => 0,
            'e_b_id' => $b['b_id'],
            'e_r_id' => $focus_class['r_id'],
        ), true);


        //Redirect to application:
        die(echo_json(array(
            'status' => 1,
            'hard_redirect' => '/my/class_application/'.$admissions[0]['ru_id'].'?u_key='.md5($udata['u_id'] . $application_status_salt).'&u_id='.$udata['u_id'],
        )));

	}
	
	function submit_application(){

        //When they submit the Application Questionnaire in step 2 of their admission:
        $application_status_salt = $this->config->item('application_status_salt');
	    if(!isset($_POST['ru_id']) || intval($_POST['ru_id'])<1 || !isset($_POST['u_key']) || !isset($_POST['answers']) || !isset($_POST['u_id']) || intval($_POST['u_id'])<1 || !(md5($_POST['u_id'].$application_status_salt)==$_POST['u_key'])){
	        
	        //Log engagement:
	        $this->Db_model->e_create(array(
	            'e_initiator_u_id' => ( isset($_POST['u_id']) ? intval($_POST['u_id']) : 0 ),
	            'e_message' => 'submit_application() Missing Core Inputs.',
	            'e_json' => $_POST,
	            'e_type_id' => 8, //Platform Error
	        ));
	        
	        //Display Error:
	        die('<span style="color:#FF0000;">Error: Missing Core Inputs. Report Logged for Admin to review.</span>');
	    }
	    
	    
	    //Fetch all their admissions:
	    $admissions = $this->Db_model->remix_admissions(array(
	        'ru.ru_id'	=> intval($_POST['ru_id']),
	    ));
	    
	    //Make sure we got all this data:
	    if(!(count($admissions)==1) || !isset($admissions[0]['r_id']) || !isset($admissions[0]['b_id'])){

	        //Log this error:
	        $this->Db_model->e_create(array(
	            'e_initiator_u_id' => $_POST['u_id'],
	            'e_message' => 'submit_application() failed to fetch admission data.',
	            'e_json' => $_POST,
	            'e_type_id' => 8, //Platform Error
	        ));
	        
	        //Error:
	        die('<span style="color:#FF0000;">Error: Failed to fetch admission data. Report Logged for Admin to review.</span>');
	    }

	    //Attach timestamp:
	    $_POST['answers']['pst_timestamp'] = date("Y-m-d H:i:s");

        //Log Engagement:
        $this->Db_model->e_create(array(
            'e_initiator_u_id' => $_POST['u_id'],
            'e_json' => $_POST,
            'e_type_id' => 26, //Application submitted
            'e_b_id' => $admissions[0]['b_id'],
            'e_r_id' => $admissions[0]['r_id'],
        ));

	    //Set updating data:
        $update_data = array(
            'ru_application_survey' => json_encode($_POST['answers']),
        );

        //Is this a free Project? If so, we can fast forward the payment step...
        if(doubleval($admissions[0]['r_usd_price'])==0){

            //Yes, change the status to Pending Review:
            $update_data['ru_status'] = 2;

            //Log Engagement that this is now ready
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $_POST['u_id'],
                'e_json' => $_POST,
                'e_type_id' => 30, //Free Application Completed
                'e_b_id' => $admissions[0]['b_id'],
                'e_r_id' => $admissions[0]['r_id'],
            ));

            //Notify student:
            $this->Comm_model->foundation_message(array(
                'e_initiator_u_id' => 0,
                'e_recipient_u_id' => $admissions[0]['u_id'],
                'e_c_id' => ( $admissions[0]['u_cache__fp_psid'] ? 2807 : 2805 ),
                'depth' => 0,
                'e_b_id' => $admissions[0]['b_id'],
                'e_r_id' => $admissions[0]['r_id'],
            ), true);

        }

	    //Save answers:
	    $this->Db_model->ru_update( intval($_POST['ru_id']) , $update_data);

	    
	    //We're good now, lets redirect to application status page and MAYBE send them to paypal asap:
	    //The "pay_r_id" variable makes the next page redirect to paypal automatically for PAID classes
	    //Show message & redirect:
	    echo '<script> setTimeout(function() { window.location = "/my/applications?pay_r_id='.$admissions[0]['r_id'].'&u_key='.$_POST['u_key'].'&u_id='.$_POST['u_id'].'" }, 1000); </script>';
	    echo '<span><img src="/img/round_done.gif?time='.time().'" class="loader"  /></span><div>Successfully Submitted 🙌​</div>';
	}

	function withdraw_application(){
        //Validate inputs:
        $application_status_salt = $this->config->item('application_status_salt');
        if(!isset($_POST['u_key']) || !isset($_POST['u_id']) || intval($_POST['u_id'])<1 || !isset($_POST['ru_id']) || intval($_POST['ru_id'])<1 || !(md5($_POST['u_id'].$application_status_salt)==$_POST['u_key'])){
            //Log this error:
            echo_json(array(
                'status' => 0,
                'message' => 'Error: Invalid Inputs',
            ));
        } else {
            //Attempt to withdraw user:
            $admissions = $this->Db_model->ru_fetch(array(
                'ru.ru_status'  => 0, //Initiated or higher as long as Project is running!
                'ru.ru_u_id'	=> $_POST['u_id'],
                'ru.ru_id'	    => $_POST['ru_id'],
            ));

            if(count($admissions)==1){

                //All good, withdraw:
                $this->Db_model->ru_update( $_POST['ru_id'] , array(
                    'ru_status' => -2, //For now this is the default since we don't accept partial payments
                ));

                //Log Engagement:
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => $admissions[0]['u_id'], //System
                    'e_type_id' => 66, //Application Withdraw
                    'e_b_id' => $admissions[0]['r_b_id'],
                    'e_r_id' => $admissions[0]['r_id'],
                ));

                //Inform User:
                echo_json(array(
                    'status' => 1,
                    'message' => status_bible('ru',-2,0,'top'),
                ));

            } else {

                //Error, Inform User:
                echo_json(array(
                    'status' => 0,
                    'message' => 'Error: Withdraw no longer possible as your application status has changed.',
                ));

            }
        }
    }

    function refresh_integration(){

        $udata = auth(2,0,$_POST['b_id']);
        if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Session. Refresh the page and try again.',
            ));
        } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project ID',
            ));
        } elseif(!isset($_POST['fp_id']) || strlen($_POST['fp_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Page ID',
            ));
        } else {

            //Fetch Page:
            $fp_pages = $this->Db_model->fp_fetch(array(
                'fp_id' => $_POST['fp_id'],
            ));

            if(count($fp_pages)<1){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Could not find page',
                ));
            } elseif(!($fp_pages[0]['fp_status']==1)){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Page is not currently integrated',
                ));
            } else {

                //First remove integration:
                $this->Comm_model->fb_page_integration($udata['u_id'],$fp_pages[0],$_POST['b_id'],0);

                //Then add integration:
                sleep(2);

                $this->Comm_model->fb_page_integration($udata['u_id'],$fp_pages[0],$_POST['b_id'],1);

                //Let user know:
                echo_json(array(
                    'status' => 1,
                    'message' => '<i class="fa fa-check-circle" aria-hidden="true" data-toggle="tooltip" title="Success"></i>',
                ));
            }
        }
    }

	function login(){
	    
	    //Setting for admin logins:
	    $master_password = 'pi980ollmaster';
        $website = $this->config->item('website');
	    
	    if(!isset($_POST['u_email']) || !filter_var($_POST['u_email'], FILTER_VALIDATE_EMAIL)){
	        redirect_message('/login','<div class="alert alert-danger" role="alert">Error: Enter valid email to continue.</div>');
	        return false;
	    } elseif(!isset($_POST['u_password'])){
	        redirect_message('/login','<div class="alert alert-danger" role="alert">Error: Enter valid password to continue.</div>');
	        return false;
	    }
	    
	    //Fetch user data:
	    $users = $this->Db_model->u_fetch(array(
	        'u_email' => strtolower($_POST['u_email']),
	    ));

        //See if they have any active admissions:
        $admissions = array();
	    if(count($users)==1){

            //TODO remove login block for launch of v2.6
            if($users[0]['u_status']<3){
                redirect_message('/login','<div class="alert alert-danger" role="alert">Error: Mench beta console is not live yet.</div>');
                return false;
            }

            $admissions = $this->Db_model->remix_admissions(array(
                'ru_u_id' => $users[0]['u_id'],
                'ru_status >=' => 0, //We would drill into this further...
            ));
        }
	    
	    if(count($users)==0){

	        //Not found!
	        redirect_message('/login','<div class="alert alert-danger" role="alert">Error: '.$_POST['u_email'].' not found.</div>');
	        return false;

	    } elseif($users[0]['u_status']<0){

	        //Inactive account
	        $this->Db_model->e_create(array(
	            'e_initiator_u_id' => $users[0]['u_id'],
	            'e_message' => 'login() denied because account is not active.',
	            'e_json' => $_POST,
	            'e_type_id' => 9, //Support Needing Graceful Errors
	        ));
	        redirect_message('/login','<div class="alert alert-danger" role="alert">Error: Your account has been de-activated. Contact us to re-active your account.</div>');
	        return false;

	    } elseif(!($_POST['u_password']==$master_password) && !($users[0]['u_password']==md5($_POST['u_password']))){

	        //Bad password
	        redirect_message('/login','<div class="alert alert-danger" role="alert">Error: Incorrect password for '.$_POST['u_email'].'.</div>');
	        return false;

	    }

        $co_instructors = array();
        if($users[0]['u_status']==1){
            //Regular user, see if they are assigned to any Project as co-instructor
            $co_instructors = $this->Db_model->user_projects(array(
                'ba.ba_u_id' => $users[0]['u_id'],
                'ba.ba_status >=' => 1,
                'b.b_status >=' => 2,
            ));
        }

        $session_data = array();
	    //Are they admin?
	    if($users[0]['u_status']>=2 /* || count($co_instructors)>0 */){
	        //They have admin rights:
            $session_data['user'] = $users[0];
        }
        //Are they an active student?
        if(count($admissions)>0){
            //They have admin rights:
            $session_data['uadmission'] = $admissions[0];
        }

        //Applicable for instructors only:
        $is_chrome = (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')!==false || strpos($_SERVER['HTTP_USER_AGENT'], 'CriOS')!==false);
        $is_instructor = (isset($session_data['user']) && count($session_data['user'])>0);
        $is_student = (isset($session_data['uadmission']) && count($session_data['uadmission'])>0);

        if($is_instructor && !$is_chrome){
            redirect_message('/login','<div class="alert alert-danger" role="alert">Error: Login Denied. Mench Console v'.$website['version'].' support <a href="https://www.google.com/chrome/browser/" target="_blank"><u>Google Chrome</u></a> only.<br />Wanna know why? <a href="https://support.mench.co/hc/en-us/articles/115003469471"><u>Continue Reading</u> &raquo;</a></div>');
            return false;
        } elseif(!$is_instructor && !$is_student){
            //We assume this is a student request:
            redirect_message('/login','<div class="alert alert-danger" role="alert">Error: You have not been admitted to any Projects yet. You can only login as a student after you have been approved by your instructor.</div>');
            return false;
        }


        //Log engagement
        if(!($_POST['u_password']==$master_password)){
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $users[0]['u_id'],
                'e_json' => $users[0],
                'e_type_id' => 10, //login
            ));
        }

        
        //All good to go!
        //Load session and redirect:
        $this->session->set_userdata($session_data);
        
        //Append user IP and agent information
        if(isset($_POST['u_password'])){
            unset($_POST['u_password']); //Sensitive information to be removed and NOT logged
        }
        $users[0]['login_ip'] = $_SERVER['REMOTE_ADDR'];
        $users[0]['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $users[0]['input_post_data'] = $_POST;

            
        
        if(isset($_POST['url']) && strlen($_POST['url'])>0){
            header( 'Location: '.$_POST['url'] );
        } else {
            //Default:
            if($is_instructor){
                //Instructor default:
                header( 'Location: /console' );
            } else {
                //Student default:
                header( 'Location: /my/actionplan' );
            }
        }
	}
	
	function logout(){
	    //Log engagement:
	    $udata = $this->session->userdata('user');
	    $this->Db_model->e_create(array(
	        'e_initiator_u_id' => ( isset($udata['u_id']) && $udata['u_id']>0 ? $udata['u_id'] : 0 ),
	        'e_json' => $udata,
	        'e_type_id' => 11, //Admin Logout
	    ));
	    
	    //Called via AJAX to destroy user session:
	    $this->session->sess_destroy();
	    header( 'Location: /' );
	}
	
	function account_update(){
	    
	    //Auth user and check required variables:
	    $udata = auth(2);
	    $countries_all = $this->config->item('countries_all');
	    $timezones = $this->config->item('timezones');
        $message_max = $this->config->item('message_max');
	    
	    if(!$udata){
	        die('<span style="color:#FF0000;">Error: Invalid Session. Refresh the page and try again.</span>');
	    } elseif(!isset($_POST['u_id']) || intval($_POST['u_id'])<=0){
	        die('<span style="color:#FF0000;">Error: Invalid ID. Try again.</span>');
	    } elseif(!isset($_POST['u_fname']) || strlen($_POST['u_fname'])<=0){
	        die('<span style="color:#FF0000;">Error: Missing First Name. Try again.</span>');
	    } elseif(!isset($_POST['u_lname']) || strlen($_POST['u_lname'])<=0){
	        die('<span style="color:#FF0000;">Error: Missing last name. Try again.</span>');
        } elseif(!isset($_POST['u_email']) || !filter_var($_POST['u_email'], FILTER_VALIDATE_EMAIL)){
            die('<span style="color:#FF0000;">Error: Missing email. Try again.</span>');
        } elseif(strlen($_POST['u_paypal_email'])>0 && !filter_var($_POST['u_paypal_email'], FILTER_VALIDATE_EMAIL)){
            die('<span style="color:#FF0000;">Error: Invalid Paypal Email. Try again.</span>');
	    } elseif(strlen($_POST['u_image_url'])>0 && (!filter_var($_POST['u_image_url'], FILTER_VALIDATE_URL) || substr($_POST['u_image_url'],0,8)!=='https://')){
	        die('<span style="color:#FF0000;">Error: Invalid HTTPS profile picture url. Try again.</span>');
	    } elseif(strlen($_POST['u_bio'])>$message_max){
	        die('<span style="color:#FF0000;">Error: Introductory Message should be less than '.$message_max.' characters. Try again.</span>');
	    }
	    
	    if(!isset($_POST['u_language'])){
	        $_POST['u_language'] = array();
	    }
	    
	    //Fetch current data:
	    $u_current = $this->Db_model->u_fetch(array(
	        'u_id' => intval($_POST['u_id']),
	    ));
	    
	    $u_update = array(
	        'u_fname' => trim($_POST['u_fname']),
	        'u_lname' => trim($_POST['u_lname']),
	        'u_email' => trim(strtolower($_POST['u_email'])),
	        'u_phone' => $_POST['u_phone'],
	        'u_image_url' => $_POST['u_image_url'],
	        'u_gender' => $_POST['u_gender'],
	        'u_country_code' => $_POST['u_country_code'],
	        'u_current_city' => $_POST['u_current_city'],
	        'u_timezone' => $_POST['u_timezone'],
	        'u_language' => join(',',$_POST['u_language']),
	        'u_bio' => trim($_POST['u_bio']),
            'u_skype_username' => trim($_POST['u_skype_username']),
            'u_paypal_email' => ( isset($_POST['u_paypal_email']) ? trim(strtolower($_POST['u_paypal_email'])) : null ),
	    );
	    
	    //Some more checks:
	    if(!(count($u_current)==1)){
	        die('<span style="color:#FF0000;">Error: Invalid user ID.</span>');
	    } elseif(strlen($_POST['u_password_new'])>0 || strlen($_POST['u_password_current'])>0){
	        //Password update attempt, lets check:
	        if(strlen($_POST['u_password_new'])<=0){
	            die('<span style="color:#FF0000;">Error: Missing new password. Try again.</span>');
	        } elseif(strlen($_POST['u_password_current'])<=0){
	            die('<span style="color:#FF0000;">Error: Missing current password. Try again.</span>');
	        } elseif(!(md5($_POST['u_password_current'])==$u_current[0]['u_password'])){
	            die('<span style="color:#FF0000;">Error: Invalid current password. Try again.</span>');
	        } elseif($_POST['u_password_new']==$_POST['u_password_current']){
	            die('<span style="color:#FF0000;">Error: New and current password cannot be the same. Try again.</span>');
	        } elseif(strlen($_POST['u_password_new'])<6){
	            die('<span style="color:#FF0000;">Error: New password must be longer than 6 characters. Try again.</span>');
	        } else {
	            //Set password for updating:
	            $u_update['u_password'] = md5($_POST['u_password_new']);
	            //Reset both fields:
	            echo "<script>$('#u_password_current').val('');$('#u_password_new').val('');</script>";
	        }
	    }
	    $warning = NULL;
	    
	    //Check social links:
	    if($_POST['u_website_url']!==$u_current[0]['u_website_url']){
	        if(strlen($_POST['u_website_url'])>0){
	            //Validate it:
	            if(filter_var($_POST['u_website_url'], FILTER_VALIDATE_URL)){
	                $u_update['u_website_url'] = $_POST['u_website_url'];
	                echo "<script>$('#u_password_current').val('');$('#u_password_new').val('');</script>";
	            } else {
	                $warning .= 'Invalid website URL. ';
	            }
	        } else {
	            $u_update['u_website_url'] = '';
	        }
	    }
	    
	    
	    //Did they just agree to the agreement?
	    if(isset($_POST['u_newly_checked']) && intval($_POST['u_newly_checked']) && strlen($u_current[0]['u_terms_agreement_time'])<1){
	        //Yes they did, save the timestamp:
	        $u_update['u_terms_agreement_time'] = date("Y-m-d H:i:s");
	    }
	    
	    
	    $u_social_account = $this->config->item('u_social_account');
	    foreach($u_social_account as $sa_key=>$sa_value){
	        if($_POST[$sa_key]!==$u_current[0][$sa_key]){
	            if(strlen($_POST[$sa_key])>0){
	                //User has attempted to update it, lets validate it:
	                //$full_url = $sa_value['sa_prefix'].trim($_POST[$sa_key]).$sa_value['sa_postfix'];
	                $u_update[$sa_key] = trim($_POST[$sa_key]);
	            } else {
	                $u_update[$sa_key] = '';
	            }
	        }
	    }
	    
	    //Now update the DB:
	    $this->Db_model->u_update(intval($_POST['u_id']) , $u_update);


	    //Refetch some DB (to keep consistency with login session format) & update the Session:
        $users = $this->Db_model->u_fetch(array(
            'u_id' => intval($_POST['u_id']),
        ));
        if(isset($users[0])){
            $this->session->set_userdata(array('user' => $users[0]));
        }


	    //Remove sensitive data before logging:
	    unset($_POST['u_password_new']);
	    unset($_POST['u_password_current']);
	    
	    //Log engagement:
	    $this->Db_model->e_create(array(
	        'e_initiator_u_id' => $udata['u_id'], //The user that updated the account
	        'e_message' => readable_updates($u_current[0],$u_update,'u_'),
	        'e_json' => array(
	            'input' => $_POST,
	            'before' => $u_current[0],
	            'after' => $u_update,
	        ),
	        'e_type_id' => 12, //Account Update
	        'e_recipient_u_id' => intval($_POST['u_id']), //The user that their account was updated
	    ));
	    
	    //TODO update algolia
	    
	    //Show result:
	    echo ( $warning ? '<span style="color:#FF8C00;">Saved all except: '.$warning.'</span>' : '<span><img src="/img/round_done.gif?time='.time().'" class="loader"  /></span>');
	}

    function completion_report(){

	    //Validate integrity of request:
        if(!isset($_POST['u_id']) || intval($_POST['u_id'])<=0
            || !isset($_POST['page_load_time']) || !intval($_POST['page_load_time'])
            || !isset($_POST['s_key']) || !($_POST['s_key']==md5($_POST['c_id'].$_POST['page_load_time'].'pag3l0aDSla7'.$_POST['u_id']))
            || !isset($_POST['b_id']) || intval($_POST['b_id'])<=0
            || !isset($_POST['r_id']) || intval($_POST['r_id'])<=0
            || !isset($_POST['c_id']) || intval($_POST['c_id'])<=0){
            die('<span style="color:#FF0000;">Error: Missing Core Data</span>');
        }

        //Fetch student name and details:
        $matching_admissions = $this->Db_model->ru_fetch(array(
            'ru_u_id' => intval($_POST['u_id']),
            'ru_r_id' => intval($_POST['r_id']),
            'ru_status >=' => 4, //Only Active students can submit Steps
        ));

        if(!(count($matching_admissions)==1)){
            die('<span style="color:#FF0000;">Error: You can no longer submit Steps</span>');
        }


        //Fetch full Project/Class/Intent data from Action Plan copy:
        $bs = fetch_action_plan_copy(intval($_POST['b_id']),intval($_POST['r_id']));
        $focus_class = $bs[0]['this_class'];
        $intent_data = extract_level( $bs[0] , intval($_POST['c_id']) );


        if(!$focus_class){
            die('<span style="color:#FF0000;">Error: Invalid Class ID!</span>');
        } elseif(!isset($intent_data['intent']) || !is_array($intent_data['intent'])){
            die('<span style="color:#FF0000;">Error: Invalid Step ID</span>');
        //Submission settings:
        } elseif($intent_data['intent']['c_complete_url_required']=='t' && count(extract_urls($_POST['us_notes']))<1){
            die('<span style="color:#FF0000;">Error: URL Required. <a href=""><b><u>Refresh this page</u></b></a> and try again.</span>');
        } elseif($intent_data['intent']['c_complete_notes_required']=='t' && strlen($_POST['us_notes'])<1){
            die('<span style="color:#FF0000;">Error: Notes Required. <a href=""><b><u>Refresh this page</u></b></a> and try again.</span>');
        }


        //Make sure student has not submitted this Step before:
        $us_data = $this->Db_model->us_fetch(array(
            'us_student_id' => intval($_POST['u_id']),
            'us_r_id' => intval($_POST['r_id']),
            'us_c_id' => intval($_POST['c_id']),
        ));

        if(count($us_data)>0 && $us_data[0]['us_status']==1){
            die('<span style="color:#FF0000;">Error: You have already marked this Step as complete,yYou cannot re-submit it.</span>');
        }

        //If we have a row that means us_status!=1, which means this is a resubmission
        //TODO implement this once we have instructor review workflow
        $is_resubmission = (count($us_data)>0);

        //Calculate total new progress based on this new this submission:
        $ru_cache__completion_rate = number_format( ( $matching_admissions[0]['ru_cache__completion_rate'] + ($intent_data['intent']['c_time_estimate']/$bs[0]['c__estimated_hours']) ),3);


        //Now update the DB:
        $us_data = $this->Db_model->us_create(array(
            'us_student_id' => $matching_admissions[0]['u_id'],
            'us_b_id' => intval($_POST['b_id']),
            'us_r_id' => intval($_POST['r_id']),
            'us_c_id' => intval($_POST['c_id']),
            'us_time_estimate' => $intent_data['intent']['c_time_estimate'], //A snapshot of its time-estimate upon completion, the Action Plan Copy might be updated later on...
            'us_student_notes' => trim($_POST['us_notes']),
            'us_status' => 1, //Submitted
        ));


        //Do we need to send any notifications?
        if(strlen(trim($_POST['us_notes']))>0 && !($matching_admissions[0]['u_id']==1) /* Shervin does a lot of testing...*/ ){

            //Send email to all instructors of this Project:
            $b_instructors = $this->Db_model->ba_fetch(array(
                'ba.ba_b_id' => intval($_POST['b_id']),
                'ba.ba_status >=' => 2, //co-instructors & lead instructor
                'u.u_status >=' => 1, //Must be a user level 1 or higher
            ));

            $student_name = ( isset($matching_admissions[0]['u_fname']) && strlen($matching_admissions[0]['u_fname'])>0 ? $matching_admissions[0]['u_fname'].' '.$matching_admissions[0]['u_lname'] : 'System' );

            $subject = '⚠️ Review Step Completion '.( strlen(trim($_POST['us_notes']))>0 ? 'Comment' : '(Without Comment)' ).' by '.$student_name;
            $div_style = ' style="padding:5px 0; font-family: Lato, Helvetica, sans-serif; font-size:16px;"';
            //Send notifications to current instructor
            foreach($b_instructors as $bi){
                //Make sure this instructor has an email on file
                if(strlen($bi['u_email'])>0){
                    //Step Completion Email:
                    //Draft HTML message for this:
                    $html_message  = '<div'.$div_style.'>Hi '.$bi['u_fname'].' 👋​</div>';
                    $html_message .= '<br />';
                    $html_message .= '<div'.$div_style.'>A new Step Completion report is ready for your review:</div>';
                    $html_message .= '<br />';
                    $html_message .= '<div'.$div_style.'>Project: '.$bs[0]['c_objective'].'</div>';
                    $html_message .= '<div'.$div_style.'>Class: '.time_format($focus_class['r_start_date'],2).'</div>';
                    $html_message .= '<br />';
                    $html_message .= '<div'.$div_style.'>Student: '.$student_name.'</div>';
                    $html_message .= '<div'.$div_style.'>Step: '.$intent_data['intent']['c_objective'].'</div>';
                    $html_message .= '<div'.$div_style.'>Estimated Time: '.echo_time($intent_data['intent']['c_time_estimate'],0).'</div>';
                    $html_message .= '<div'.$div_style.'>Completion Notes: '.( strlen(trim($_POST['us_notes']))>0 ? nl2br(trim($_POST['us_notes'])) : 'None' ).'</div>';
                    $html_message .= '<br />';
                    $html_message .= '<div'.$div_style.'>Cheers,</div>';
                    $html_message .= '<div'.$div_style.'>Team Mench</div>';
                    $html_message .= '<div><img src="https://s3foundation.s3-us-west-2.amazonaws.com/c65a5ea7c0dd911074518921e3320439.png" /></div>';
                    //Send Email:
                    $this->Comm_model->send_email(array($bi['u_email']), $subject, $html_message, array(
                        'e_initiator_u_id' => ( isset($matching_admissions[0]['u_id']) ? $matching_admissions[0]['u_id'] : 0 ), //Student who made submission
                        'e_recipient_u_id' => $bi['u_id'], //The admin
                        'e_message' => $subject,
                        'e_json' => array(
                            'html' => $html_message,
                        ),
                        'e_type_id' => 28, //Email message sent
                        'e_c_id' => $intent_data['intent']['c_id'],
                        'e_b_id' => intval($_POST['b_id']),
                        'e_r_id' => $focus_class['r_id'],
                    ));
                }
            }
        }


        //See if we need to dispatch any messages:
        $on_complete_messages = array();
        $drip_messages = array();

        //Dispatch messages for this Step:
        $step_messages = extract_level($bs[0],$_POST['c_id']);

        foreach($step_messages['intent']['c__messages'] as $i){
            if($i['i_status']==2){
                //Add a reference button to Drip messages:
                $i['i_message'] = $i['i_message'].' {button}';
                array_push($drip_messages , $i);
            } elseif($i['i_status']==3){
                array_push($on_complete_messages, array_merge($i , array(
                    'e_initiator_u_id' => 0,
                    'e_recipient_u_id' => $matching_admissions[0]['u_id'],
                    'i_c_id' => $i['i_c_id'],
                    'e_b_id' => intval($_POST['b_id']),
                    'e_r_id' => intval($_POST['r_id']),
                )));
            }
        }

        //Is a Task Completed? Dispatch potential messages:
        if($intent_data['next_level']<=2){

            //The Task does seem complete:
            foreach($step_messages['step_task']['c__messages'] as $i){
                if($i['i_status']==2){

                    //Add a reference button to Drip messages:
                    $i['i_message'] = $i['i_message'].' {button}';
                    array_push($drip_messages, $i);

                } elseif($i['i_status']==3){

                    array_push($on_complete_messages, array_merge($i , array(
                        'e_initiator_u_id' => 0,
                        'e_recipient_u_id' => $matching_admissions[0]['u_id'],
                        'i_c_id' => $i['i_c_id'],
                        'e_b_id' => intval($_POST['b_id']),
                        'e_r_id' => intval($_POST['r_id']),
                    )));

                }
            }

            //Is the Project Complete?
            if($intent_data['next_level']==1){
                //Seems so!
                foreach($bs[0]['c__messages'] as $i){
                    //Projects only could have ON-COMPLETE messages:
                    if($i['i_status']==3){
                        array_push($on_complete_messages, array_merge($i , array(
                            'e_initiator_u_id' => 0,
                            'e_recipient_u_id' => $matching_admissions[0]['u_id'],
                            'i_c_id' => $i['i_c_id'],
                            'e_b_id' => intval($_POST['b_id']),
                            'e_r_id' => intval($_POST['r_id']),
                        )));
                    }
                }
            }
        }

        //Anything to be sent instantly?
        if(count($on_complete_messages)>0){
            //Dispatch all Instant Messages, their engagements have already been logged:
            $this->Comm_model->send_message($on_complete_messages);
        }

        //Any Drip Messages? Schedule them if we have some:
        if(count($drip_messages)>0){

            $start_time = time();
            $drip_intervals = ($focus_class['r__class_end_time']-$start_time) / (count($drip_messages)+1);
            $drip_time = $start_time;

            foreach($drip_messages as $i){

                $drip_time += $drip_intervals;
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => 0, //System
                    'e_recipient_u_id' => $matching_admissions[0]['u_id'],
                    'e_timestamp' => date("Y-m-d H:i:s" , $drip_time ), //Used by Cron Job to fetch this Drip when due
                    'e_json' => array(
                        'created_time' => date("Y-m-d H:i:s" , $start_time ),
                        'drip_time' => date("Y-m-d H:i:s" , $drip_time ),
                        'i_drip_count' => count($drip_messages),
                        'i' => $i, //The actual message that would be sent
                    ),
                    'e_type_id' => 52, //Pending Drip e_type_id=52
                    'e_cron_job' => 0, //Pending for the Drip Cron
                    'e_i_id' => $i['i_id'],
                    'e_c_id' => $i['i_c_id'],
                    'e_b_id' => intval($_POST['b_id']),
                    'e_r_id' => intval($_POST['r_id']),
                    'e_fp_id' => $matching_admissions[0]['ru_fp_id'],

                ));

            }
        }



        //Show result to student:
        echo_us($us_data);



        //Log Engagement for Step Completion:
        $this->Db_model->e_create(array(
            'e_initiator_u_id' => intval($_POST['u_id']),
            'e_message' => $us_data['us_student_notes'],
            'e_json' => array(
                'input' => $_POST,
                'scheduled_drip' => count($drip_messages),
                'sent_oncomplete' => count($on_complete_messages),
                'next_level' => $intent_data['next_level'],
                'next_c' => ( isset($intent_data['next_intent']) ? $intent_data['next_intent'] : array() ),
            ),
            'e_type_id' => 33, //Marked as Done Report
            'e_b_id' => intval($_POST['b_id']),
            'e_r_id' => intval($_POST['r_id']),
            'e_c_id' => intval($_POST['c_id']),
        ));


        //Take action based on what the next level is...
        if($intent_data['next_level']==1){

            //The next level is the Project, which means this was the last Step:
            $this->Db_model->ru_update( $matching_admissions[0]['ru_id'] , array(
                'ru_cache__completion_rate' => $ru_cache__completion_rate, //Should be 1 (100% complete)
                'ru_cache__current_task' => ($focus_class['r__total_tasks']+1), //Go 1 Task after the total Tasks to indicate completion
            ));

            //Send graduation message:
            $this->Comm_model->foundation_message(array(
                'e_recipient_u_id' => intval($_POST['u_id']),
                'e_c_id' => 4632, //As soon as Graduated message
                'depth' => 0,
                'e_b_id' => intval($_POST['b_id']),
                'e_r_id' => intval($_POST['r_id']),
            ));

        } elseif($intent_data['next_level']==2){

            //We have a next Task:
            //We also need to change ru_cache__current_task to reflect this advancement:
            $this->Db_model->ru_update( $matching_admissions[0]['ru_id'] , array(
                'ru_cache__completion_rate' => $ru_cache__completion_rate,
                'ru_cache__current_task' => $intent_data['next_intent']['cr_outbound_rank'],
            ));

            //Send appropriate Message:
            $this->Comm_model->foundation_message(array(
                'e_recipient_u_id' => intval($_POST['u_id']),
                'e_c_id' => $intent_data['next_intent']['c_id'],
                'depth' => 0,
                'e_b_id' => intval($_POST['b_id']),
                'e_r_id' => intval($_POST['r_id']),
            ));

        } elseif($intent_data['next_level']==3){

            //This is the next Step, update the student positioning here...
            $this->Db_model->ru_update( $matching_admissions[0]['ru_id'] , array(
                'ru_cache__completion_rate' => $ru_cache__completion_rate,
            ));

            //Show button for next Step:
            echo '<div style="font-size:1.2em;"><a href="/my/actionplan/'.$_POST['b_id'].'/'.$intent_data['next_intent']['c_id'].'" class="btn btn-black">Next <i class="fa fa-arrow-right"></i></a></div>';

        } else {

            //This should not happen!
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $_POST['u_id'],
                'e_message' => 'completion_report() experienced fatal error where $intent_data/next_level was not 1,2 or 3',
                'e_json' => $_POST,
                'e_type_id' => 8, //Platform Error
                'e_b_id' => $_POST['b_id'],
                'e_r_id' => $_POST['r_id'],
                'e_c_id' => $_POST['c_id'],
            ));

        }
    }

    /* ******************************
     * r Classes
     ****************************** */

    function sync_action_plan(){
        $udata = auth(2,0,$_POST['b_id']);
        if(!$udata){
            echo '<span style="color:#FF0000;">Error: Session Expired.</span>';
        } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
            echo '<span style="color:#FF0000;">Error: Missing Project ID.</span>';
        } elseif(!isset($_POST['r_id']) || intval($_POST['r_id'])<=0){
            echo '<span style="color:#FF0000;">Error: Missing Class ID.</span>';
        } else {
            //All Good, start updating:
            $saved = $this->Db_model->snapshot_action_plan($_POST['b_id'],$_POST['r_id']);
            //Show Message:
            if($saved){
                echo '<span><img src="/img/round_done.gif?time='.time().'" class="loader"  /></span>';
            } else {
                echo '<span style="color:#FF0000;">Unknown Error while trying to save the Action Plan</span>';
            }
        }
    }

	function load_iphone(){
        $udata = auth(1);
        if(!$udata){
            //Display error:
            die('<span style="color:#FF0000;">Error: Invalid Session. Login again to continue.</span>');
        } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
            die('<span style="color:#FF0000;">Error: Invalid Project ID</span>');
        } elseif(!isset($_POST['c_id']) || intval($_POST['c_id'])<=0){
            die('<span style="color:#FF0000;">Error: Invalid Intent id.</span>');
        } elseif(!isset($_POST['level']) || intval($_POST['level'])<=0){
            die('<span style="color:#FF0000;">Error: invalid level ID.</span>');
        } else {
            //Load the phone:
            $this->load->view('console/frames/messages' , $_POST);
        }
    }




    function load_classmates(){

	    //Function called form /MY/classmates (student Messenger)
        if(isset($_POST['psid'])){

            $ru_filter = array(
                'ru.ru_status >=' => 2, //Completed Application
                'r.r_status >=' => 1, //Open for Admission or Higher
            );

            if($_POST['psid']==0){
                //Data is supposed to be in the session:
                $uadmission = $this->session->userdata('uadmission');
                $ru_filter['ru.ru_u_id'] = $uadmission['u_id'];
            } else {
                $ru_filter['ru.ru_fp_psid'] = $_POST['psid'];
            }

            //Fetch all their admissions:
            $admissions = $this->Db_model->remix_admissions($ru_filter);
            $active_admission = filter_active_admission($admissions); //We'd need to see which admission to load

            if(!$active_admission){

                //Ooops, they dont have anything!
                die('<div class="alert alert-danger" role="alert">You have not joined any Projects yet</div>');

            } else {

                //Log Engagement for opening the classmates:
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => $active_admission['u_id'],
                    'e_type_id' => 54, //classmates Opened
                    'e_b_id' => $active_admission['b_id'],
                    'e_r_id' => $active_admission['r_id'],
                ));

                //Show Classmates:
                echo_classmates($active_admission['b_id'],$active_admission['r_id'],0);

            }
        } else {
            die('<span style="color:#FF0000;">Error: Missing PSID</span>');
        }
    }


	function simulate_task(){
	    //Dispatch Messages:
        $results = $this->Comm_model->foundation_message(array(
            'e_recipient_u_id' => $_POST['u_id'],
            'e_c_id' => $_POST['c_id'],
            'depth' => $_POST['depth'],
            'e_b_id' => $_POST['b_id'],
            'e_r_id' => 0,
        ));

        if($results['status']){
            echo '<i class="fa fa-check-circle" style="color:#3C4858;" title="SUCCESS: '.$results['message'].'" aria-hidden="true"></i>';
        } else {
            echo '<i class="fa fa-exclamation-triangle" style="color:#FF0000;" title="ERROR: '.$results['message'].'" aria-hidden="true"></i>';
        }
    }


	function r_create(){
	    $udata = auth(2);
	    if(!$udata){
	        //Display error:
	        die('<span style="color:#FF0000;">Error: Invalid Session. Refresh the Page to Continue.</span>');
        } elseif(!isset($_POST['r_start_date']) || !strtotime($_POST['r_start_date'])){
            die('<span style="color:#FF0000;">Error: Enter valid start date.</span>');
        } elseif((strtotime($_POST['r_start_date']))<time()){
            die('<span style="color:#FF0000;">Error: Cannot have a start date in the past.</span>');
	    } elseif(!isset($_POST['r_b_id']) || intval($_POST['r_b_id'])<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Project ID.</span>');
	    } elseif(!isset($_POST['r_status'])){
	        die('<span style="color:#FF0000;">Error: Invalid class status.</span>');
	    } elseif(!isset($_POST['copy_r_id']) || intval($_POST['copy_r_id'])<0){
	        die('<span style="color:#FF0000;">Error: Missing Class Import Settings.</span>');
	    } else {
	        
	        //Start with the date:
	        $new_date = date("Y-m-d",strtotime($_POST['r_start_date']));
	        //Check for unique start date:
	        $current_classes = $this->Db_model->r_fetch(array(
	            'r.r_b_id' => intval($_POST['r_b_id']),
	            'r.r_status >=' => 0,
	            'r.r_start_date' => $new_date,
	        ));
	        if(count($current_classes)>0){
	            //Ooops, we cannot have duplicate dates:
	            die('<span style="color:#FF0000;">Error: Cannot have two classes starting on the same day.</span>');
	        }
	        
	        
	        //This is the variable we're building:
	        $class_data = null;
	        
	        if(intval($_POST['copy_r_id'])>0){
	            
	            //Fetch default questions:
	            $classes = $this->Db_model->r_fetch(array(
	                'r.r_id' => intval($_POST['copy_r_id']),
	                'r.r_b_id' => intval($_POST['r_b_id']),
	            ));
	            
	            if(count($classes)==1){

	                //Port the settings:
	                $class_data = $classes[0];
	                $eng_message = time_format($_POST['r_start_date'],1).' class created by copying '.time_format($class_data['r_start_date'],1).' class settings.';
	                
	                //Make some adjustments
	                $class_data['r_start_date'] = $new_date;
	                $class_data['r_status'] = intval($_POST['r_status']); //Override with input status

	                //Remove all additional appended data and certain non-replicatable data fields like r_id:
                    foreach($class_data as $key=>$value){
                        if(substr_count($key,'r__')>0 || in_array($key,array('r_id'))){
                            //This is an appended data field:
                            unset($class_data[$key]);
                        }
                    }
	            }
	        }
	        
	        
	        //Did we build it?
	        if(!$class_data){
	            
	            //Generate core data:
	            $class_data = array(
	                'r_b_id' => intval($_POST['r_b_id']),
	                'r_start_date' => date("Y-m-d",strtotime($_POST['r_start_date'])),
	                'r_status' => intval($_POST['r_status']),
	            );
	            
	            //Default message:
	            $eng_message = time_format($_POST['r_start_date'],1).' class created form scratch.';
	        }
	        
	        
	        //Create new class:
            $class = $this->Db_model->r_create($class_data);

	        
	        //Log engagement:
	        $this->Db_model->e_create(array(
	            'e_initiator_u_id' => $udata['u_id'], //The user that updated the account
	            'e_message' => $eng_message,
	            'e_json' => array(
	                'input' => $_POST,
	                'before' => array(),
	                'after' => $class,
	            ),
	            'e_type_id' => 14, //New Class
	            'e_b_id' => intval($_POST['r_b_id']), //Share with Project team
	            'e_r_id' => $class['r_id'],
	        ));
	        
	        
	        if($class['r_id']>0){
	            //Redirect:
	            echo '<script> window.location = "/console/'.intval($_POST['r_b_id']).'/classes/'.$class['r_id'].'" </script>';
	        } else {
	            die('<span style="color:#FF0000;">Error: Unkown error while trying to create this Project.</span>');
	        }
	    }
	}
	
	function update_schedule(){
	    $udata = auth(2);
	    if(!$udata){
	        //Display error:
	        die('<span style="color:#FF0000;">Error: Invalid Session. Refresh the Page to Continue.</span>');
	    } elseif(!isset($_POST['hours']) || !is_array($_POST['hours'])){
	        //TODO make sure its monday
	        die('<span style="color:#FF0000;">Error: Missing hours.</span>');
	    } elseif(!isset($_POST['r_id']) || intval($_POST['r_id'])<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Class ID.</span>');
	    }
	    
	    //Fetch for the record:
	    $classes = $this->Db_model->r_fetch(array(
	        'r.r_id' => intval($_POST['r_id']),
	    ));
	    if(count($classes)<1){
	        //Ooops, not found!
	        die('<span style="color:#FF0000;">Error: Class ID not found.</span>');
	    }
	    
	    
	    $blank_template = 'a:7:{i:0;a:1:{s:3:"day";s:1:"0";}i:1;a:1:{s:3:"day";s:1:"1";}i:2;a:1:{s:3:"day";s:1:"2";}i:3;a:1:{s:3:"day";s:1:"3";}i:4;a:1:{s:3:"day";s:1:"4";}i:5;a:1:{s:3:"day";s:1:"5";}i:6;a:1:{s:3:"day";s:1:"6";}}';
	    $prepped_input = trim(serialize($_POST['hours']));
	    $r_update = array(
	        'r_live_office_hours' => ( strlen($prepped_input)>0 && !($prepped_input==$blank_template) ? $prepped_input : null ),
	    );
	    $this->Db_model->r_update( intval($_POST['r_id']) , $r_update);
	    
	    //Log engagement ONLY if different:
	    if($r_update['r_live_office_hours']!==$classes[0]['r_live_office_hours']){
	        $this->Db_model->e_create(array(
	            'e_initiator_u_id' => $udata['u_id'], //The user
	            'e_message' => readable_updates($classes[0],$r_update,'r_'),
	            'e_json' => array(
	                'input' => $_POST,
	                'before' => @unserialize($classes[0]['r_live_office_hours']),
	                'after' => @unserialize($r_update['r_live_office_hours']),
	            ),
	            'e_type_id' => 13, //Class Updated
	            'e_b_id' => $classes[0]['r_b_id'], //Share with Project team
	            'e_r_id' => intval($_POST['r_id']),
	        ));
	    }
	    
	    //Show result:
	    die('<span><img src="/img/round_done.gif?time='.time().'" class="loader"  /></span>');
	}

	function class_update_status(){

	    $udata = auth(2);
	    if(!$udata){
	        //Display error:
	        die('<span style="color:#FF0000;">Error: Invalid Session. Refresh the Page to Continue.</span>');
	    } elseif(!isset($_POST['r_id']) || intval($_POST['r_id'])<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Class ID.</span>');
	    } elseif(!isset($_POST['r_status'])){
	        die('<span style="color:#FF0000;">Error: Missing Class Status.</span>');
	    }
	    

	    //Fetch for the record:
	    $classes = $this->Db_model->r_fetch(array(
	        'r.r_id' => intval($_POST['r_id']),
	    ));
	    if(count($classes)<1){
	        //Ooops, not found!
	        die('<span style="color:#FF0000;">Error: Class ID not found.</span>');
	    }


	    //Save
	    $this->Db_model->r_update( intval($_POST['r_id']) , array(
            'r_status' => intval($_POST['r_status']),
        ));


	    //Determine what type of engagement is this?
        if($_POST['r_status']==-1 && $_POST['r_status']!=$classes[0]['r_status']){
            //Archived:
            $e_type_id = 16;
        } else {
            //Simply updated:
            $e_type_id = 13;
        }
	    
	    //Log engagement:
	    $this->Db_model->e_create(array(
	        'e_initiator_u_id' => $udata['u_id'], //The user
	        'e_message' => ( $e_type_id==13 ? readable_updates($classes[0],$r_update,'r_') : null ),
	        'e_json' => array(
	            'input' => $_POST,
	            'before' => $classes[0],
	            'after' => $r_update,
	        ),
	        'e_type_id' => $e_type_id,
	        'e_b_id' => $classes[0]['r_b_id'],
	        'e_r_id' => intval($_POST['r_id']),
	    ));
	    
	    //Show result:
	    die('<span><img src="/img/round_done.gif?time='.time().'" class="loader"  /></span>');
	}
	
	/* ******************************
	 * b Projects
	 ****************************** */
	
	function project_create(){

	    $udata = auth(2);
	    if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Session expired. Login to try again.',
            ));
            return false;
	    } elseif(!isset($_POST['c_objective']) || strlen($_POST['c_objective'])<2){
            echo_json(array(
                'status' => 0,
                'message' => 'Outcome must be 2 characters or longer.',
            ));
            return false;
	    }
	        
        //Create new intent:
        $intent = $this->Db_model->c_create(array(
            'c_creator_id' => $udata['u_id'],
            'c_objective' => trim($_POST['c_objective']),
        ));
        if(intval($intent['c_id'])<=0){
            //Log this error:
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $udata['u_id'],
                'e_message' => 'project_create() Function failed to create intent ['.$_POST['c_objective'].'].',
                'e_json' => $_POST,
                'e_type_id' => 8, //Platform Error
            ));

            echo_json(array(
                'status' => 0,
                'message' => 'Unknown error while trying to create intent.',
            ));
            return false;
        }
        
        
        //Generaye URL Key:
        //Cleans text:
        $generated_key = generate_hashtag($_POST['c_objective']);
        
        
        //Check for duplicates:
        $bs = $this->Db_model->b_fetch(array(
            'LOWER(b.b_url_key)' => strtolower($generated_key),
        ));
        if(count($bs)>0){
            //Ooops, we have a duplicate:
            $generated_key = $generated_key.'-'.rand(0,99999);
        }

        //Fetch default list values:
        $default_class_prerequisites = $this->config->item('default_class_prerequisites');
        $default_class_prizes = $this->config->item('default_class_prizes');

        //Create new Project:
        $b = $this->Db_model->b_create(array(
            'b_creator_id' => $udata['u_id'],
            'b_url_key' => $generated_key,
            'b_c_id' => $intent['c_id'],
            'b_prerequisites' => json_encode($default_class_prerequisites),
            'b_completion_prizes' => json_encode($default_class_prizes),
        ));

        if(intval($b['b_id'])<=0){
            //Log this error:
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $udata['u_id'],
                'e_message' => 'project_create() Function failed to create Project for intent #'.$intent['c_id'],
                'e_json' => $_POST,
                'e_type_id' => 8, //Platform Error
            ));
            echo_json(array(
                'status' => 0,
                'message' => 'Unknown error while trying to create Project.',
            ));
            return false;
        }
        
        //Assign permissions for this user:
        $admin_status = $this->Db_model->ba_create(array(
            'ba_creator_id' => $udata['u_id'],
            'ba_u_id' => $udata['u_id'],
            'ba_status' => 3, //Leader - As this is the first person to create
            'ba_b_id' => $b['b_id'],
            'ba_team_display' => 't', //Show on landing page
        ));
        if(intval($admin_status['ba_id'])<=0){
            //Log this error:
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $udata['u_id'],
                'e_message' => 'project_create() Function failed to grant permission for Project #'.$b['b_id'],
                'e_json' => $_POST,
                'e_type_id' => 8, //Platform Error
            ));
            echo_json(array(
                'status' => 0,
                'message' => 'Unknown error while trying to set Project leader.',
            ));
            return false;
        }

        
        //Log Engagement for Intent Created:
        $this->Db_model->e_create(array(
            'e_initiator_u_id' => $udata['u_id'],
            'e_message' => '['.$intent['c_objective'].'] created as a new intent',
            'e_json' => array(
                'input' => $_POST,
                'before' => null,
                'after' => $intent,
            ),
            'e_type_id' => 20, //Intent Created
            'e_b_id' => $b['b_id'],
            'e_c_id' => $intent['c_id'],
        ));
        
        
        //Log Engagement for Project Created:
        $this->Db_model->e_create(array(
            'e_initiator_u_id' => $udata['u_id'],
            'e_message' => 'Project #'.$b['b_id'].' created for ['.$intent['c_objective'].'] intent',
            'e_json' => array(
                'input' => $_POST,
                'before' => null,
                'after' => $b,
            ),
            'e_type_id' => 15, //Project Created
            'e_b_id' => $b['b_id'],
        ));
        
        
        //Log Engagement for Permission Granted:
        $this->Db_model->e_create(array(
            'e_initiator_u_id' => $udata['u_id'],
            'e_message' => $udata['u_fname'].' '.$udata['u_lname'].' assigned as Project Leader',
            'e_json' => array(
                'input' => $_POST,
                'before' => null,
                'after' => $admin_status,
            ),
            'e_type_id' => 25, //Permission Granted
            'e_b_id' => $b['b_id'],
        ));
        
        
        //Show message & redirect:
        //For fancy UI to give impression of hard work:
        echo_json(array(
            'status' => 1,
            'message' => echo_b(array_merge($b,$intent)),
        ));
	}

    function save_b_list(){
        //Auth user and Load object:
        $udata = auth(2);
        if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Session. Login again to Continue.',
            ));
        } elseif(!isset($_POST['group_id']) || !in_array($_POST['group_id'],array('b_target_audience','b_prerequisites','b_transformations','b_completion_prizes'))){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Group ID',
            ));
        } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0) {
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project ID',
            ));
        } else {

            //Updatye Project:
            $this->Db_model->b_update( intval($_POST['b_id']) , array(
                $_POST['group_id'] => ( isset($_POST['new_sort']) && is_array($_POST['new_sort']) && count($_POST['new_sort'])>0 ? json_encode($_POST['new_sort']) : null ),
            ));

            //Log Engagement:
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $udata['u_id'],
                'e_json' => $_POST,
                'e_type_id' => 53, //Project List Modified
                'e_b_id' => intval($_POST['b_id']),
            ));

            //Display message:
            echo_json(array(
                'status' => 1,
                'message' => '<i class="fa fa-check" aria-hidden="true"></i> Saved',
            ));
        }
    }

    function save_settings(){

        //Auth user and check required variables:
        $udata = auth(2);

        //Validate Project ID:
        if(isset($_POST['b_id'])){
            $bs = $this->Db_model->b_fetch(array(
                'b.b_id' => intval($_POST['b_id']),
            ));
        }

        if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Session Expired',
            ));
            return false;
        } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0 || count($bs)<1){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Project ID',
            ));
            return false;
	    } elseif(!isset($_POST['b_url_key'])){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project URL',
            ));
            return false;
        } elseif(!isset($_POST['b_status'])){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project Status',
            ));
            return false;
        }



        //Did any of the Project fields change?
        if(!($bs[0]['b_status']==$_POST['b_status']) || !($bs[0]['b_url_key']==$_POST['b_url_key']) || !($bs[0]['b_fb_pixel_id']==$_POST['b_fb_pixel_id'])){

            //Fetch reserved terms:
            $reserved_hashtags = $this->config->item('reserved_hashtags');

            //Validate URL Key to be unique:
            $duplicate_projects = $this->Db_model->b_fetch(array(
                'LOWER(b.b_url_key)' => strtolower($_POST['b_url_key']),
                'b.b_id !=' => $_POST['b_id'],
            ));

            //Check URL Key:
            if(in_array(strtolower($_POST['b_url_key']),$reserved_hashtags)){
                echo_json(array(
                    'status' => 0,
                    'message' => '"'.$_POST['b_url_key'].'" is a reserved URL.',
                ));
                return false;
            } elseif(strlen($_POST['b_url_key'])>30){
                echo_json(array(
                    'status' => 0,
                    'message' => 'URL Key should be less than 30 characters',
                ));
                return false;
            } elseif(strlen($_POST['b_url_key'])<4){
                echo_json(array(
                    'status' => 0,
                    'message' => 'URL Key should be at least 4 characters long',
                ));
                return false;
            } elseif(ctype_digit($_POST['b_url_key'])){
                echo_json(array(
                    'status' => 0,
                    'message' => 'URL Key should include at-least 1 letter.',
                ));
                return false;
            } elseif(!(strtolower(generate_hashtag($_POST['b_url_key']))==strtolower($_POST['b_url_key']))){
                echo_json(array(
                    'status' => 0,
                    'message' => 'URL Key can only include letters a-z and numbers 0-9',
                ));
                return false;
            } elseif(count($duplicate_projects)>0){
                echo_json(array(
                    'status' => 0,
                    'message' => 'URL Key <a href="/'.$_POST['b_url_key'].'" target="_blank">'.$_POST['b_url_key'].'</a> already taken.',
                ));
                return false;
            }


            //Update Project:
            $b_update = array(
                'b_status' => $_POST['b_status'],
                'b_url_key' => $_POST['b_url_key'],
                'b_fb_pixel_id' => ( strlen($_POST['b_fb_pixel_id'])>0 ? bigintval($_POST['b_fb_pixel_id']) : NULL ),
            );

            $this->Db_model->b_update( intval($_POST['b_id']) , $b_update );


            //Project Edit is the default engagement:
            $engagement_type_id = 18;

            //Did the status change? Log Engagement for this:
            if(!(intval($_POST['b_status'])==intval($bs[0]['b_status']))){
                if(intval($_POST['b_status'])<0){
                    //Archived
                    $engagement_type_id = 17;
                } elseif(intval($_POST['b_status'])==3) {
                    //Public in Marketplace
                    $engagement_type_id = 68;
                }
            }

            //Log engagement:
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $udata['u_id'],
                'e_message' => ( $engagement_type_id==18 ? readable_updates($bs[0],$b_update,'b_') : null ),
                'e_json' => array(
                    'input' => $_POST,
                    'before' => $bs[0],
                    'after' => $b_update,
                ),
                'e_type_id' => $engagement_type_id,
                'e_b_id' => intval($_POST['b_id']),
            ));

        }

        //Show success:
        echo_json(array(
            'status' => 1,
            'message' => '<span><img src="/img/round_done.gif?time='.time().'" class="loader"  /></span>',
        ));
    }

    function save_modify(){

        //Auth user and check required variables:
        $udata = auth(2);

        //Validate Project ID:
        $bs = $this->Db_model->b_fetch(array(
            'b.b_id' => intval($_POST['b_id']),
        ));

        //Validate Original intent:
        $original_intents = $this->Db_model->c_fetch(array(
            'c.c_id' => intval($_POST['pid']),
        ) , 0 );

        if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Session Expired',
            ));
            return false;
        } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Intent ID',
            ));
            return false;
        } elseif(!isset($_POST['level']) || intval($_POST['level'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing level',
            ));
            return false;
        } elseif(!isset($_POST['c_objective']) || strlen($_POST['c_objective'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Outcome',
            ));
            return false;
        } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project ID',
            ));
            return false;
        } elseif($_POST['level']==2 && !isset($_POST['c_status'])){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Task Status',
            ));
            return false;
        } elseif($_POST['level']>=3 && !isset($_POST['c_status'])){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Step Status',
            ));
            return false;
        } elseif($_POST['level']>=3 && !isset($_POST['c_time_estimate'])){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Time Estimate',
            ));
            return false;
        } elseif($_POST['level']>=3 && (!isset($_POST['c_complete_url_required']) || !isset($_POST['c_complete_notes_required']))){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Completion Settings',
            ));
            return false;
        } elseif(count($bs)<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Project ID',
            ));
            return false;
        } elseif(count($original_intents)<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Invalid PID',
            ));
            return false;
        }


        //Process data based on level:
        if($_POST['level']==1){

            //Did the Project's Intent Outcome change?
            if(!(trim($_POST['c_objective'])==$original_intents[0]['c_objective'])){
                //Generate Update Array
                $c_update = array(
                    'c_objective' => trim($_POST['c_objective']),
                );
            }

        } elseif($_POST['level']==2){

            $c_update = array(
                'c_objective' => trim($_POST['c_objective']),
                'c_status' => intval($_POST['c_status']),
            );

        } elseif($_POST['level']==3){

            $c_update = array(
                'c_objective' => trim($_POST['c_objective']),
                'c_status' => intval($_POST['c_status']),
                'c_time_estimate' => floatval($_POST['c_time_estimate']),
                'c_complete_url_required' => ( intval($_POST['c_complete_url_required']) ? 't' : 'f' ),
                'c_complete_notes_required' => ( intval($_POST['c_complete_notes_required']) ? 't' : 'f' ),
            );

        }



        //Did we have any intent updating to do?
        if(isset($c_update) && count($c_update)>0){

            //Now update the DB:
            $this->Db_model->c_update( intval($_POST['pid']) , $c_update);

            //Update Algolia:
            //$this->Db_model->sync_algolia(intval($_POST['pid']));

            //Log Engagement for New Intent Link:
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $udata['u_id'],
                'e_message' => readable_updates($original_intents[0],$c_update,'c_'),
                'e_json' => array(
                    'input' => $_POST,
                    'before' => $original_intents[0],
                    'after' => $c_update,
                ),
                'e_type_id' => ( $_POST['level']>=2 && isset($c_update['c_status']) && $c_update['c_status']<0 ? 21 : 19 ), //Intent Deleted OR Updated
                'e_b_id' => intval($_POST['b_id']),
                'e_c_id' => intval($_POST['pid']),
            ));

        }

        //Show success:
        echo_json(array(
            'status' => 1,
            'message' => '<span><i class="fa fa-check" aria-hidden="true"></i> Saved</span>',
        ));

	}

	/* ******************************
	 * c Intents
	 ****************************** */
	
	function intent_create(){
	    
	    $udata = auth(2);
	    if(!$udata){
	        die('<span style="color:#FF0000;">Error: Invalid Session. Refresh the Page to Continue.</span>');
	    } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Project ID.</span>');
	    } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Intent ID.</span>');
	    } elseif(!isset($_POST['c_objective']) || strlen($_POST['c_objective'])<=0){
	        die('<span style="color:#FF0000;">Error: Missing Intent Outcome.</span>');
	    }
	    
	    //Validate Original intent:
	    $inbound_intents = $this->Db_model->c_fetch(array(
	        'c.c_id' => intval($_POST['pid']),
	    ));
	    if(count($inbound_intents)<=0){
	        die('<span style="color:#FF0000;">Error: Invalid PID.</span>');
	    }
	    
	    //Validate Project ID:
	    $bs = $this->Db_model->b_fetch(array(
	        'b.b_id' => intval($_POST['b_id']),
	    ));
	    if(count($bs)<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Project ID.</span>');
	    }
	    
	    //Create intent:
	    $new_intent = $this->Db_model->c_create(array(
	        'c_creator_id' => $udata['u_id'],
            'c_objective' => trim($_POST['c_objective']),
            'c_time_estimate' => ( $_POST['next_level']>=3 ? '0.05' : '0' ), //3 min default Step
	    ));
	    
	    //Log Engagement for New Intent:
	    $this->Db_model->e_create(array(
	        'e_initiator_u_id' => $udata['u_id'],
	        'e_message' => 'Intent ['.$new_intent['c_objective'].'] created',
	        'e_json' => array(
	            'input' => $_POST,
	            'before' => null,
	            'after' => $new_intent,
	        ),
	        'e_type_id' => 20, //New Intent
	        'e_b_id' => intval($_POST['b_id']),
	        'e_c_id' => $new_intent['c_id'],
	    ));
	    
	    //Create Link:
	    $relation = $this->Db_model->cr_create(array(
	        'cr_creator_id' => $udata['u_id'],
	        'cr_inbound_id'  => intval($_POST['pid']),
	        'cr_outbound_id' => $new_intent['c_id'],
	        'cr_outbound_rank' => 1 + $this->Db_model->max_value('v5_intent_links','cr_outbound_rank', array(
                'cr_status >=' => 1,
                'c_status >=' => 1,
	            'cr_inbound_id' => intval($_POST['pid']),
	        )),
	    ));
	    
	    //Log Engagement for New Intent Link:
	    $this->Db_model->e_create(array(
	        'e_initiator_u_id' => $udata['u_id'],
	        'e_message' => 'Linked intent ['.$new_intent['c_objective'].'] as outbound of intent ['.$inbound_intents[0]['c_objective'].']',
	        'e_json' => array(
	            'input' => $_POST,
	            'before' => null,
	            'after' => $relation,
	        ),
	        'e_type_id' => 23, //New Intent Link
	        'e_b_id' => intval($_POST['b_id']),
	        'e_cr_id' => $relation['cr_id'],
	    ));
	    
	    //Fetch full link package:
	    $relations = $this->Db_model->cr_outbound_fetch(array(
	        'cr.cr_id' => $relation['cr_id'],
	    ));
	    
	    //Update Algolia:
	    //$this->Db_model->sync_algolia($new_intent['c_id']);

	    //Return result:
        echo_json(array(
            'status' => 1,
            'c_id' => $new_intent['c_id'],
            'html' => echo_cr($_POST['b_id'],$relations[0],$_POST['next_level'],intval($_POST['pid'])),
        ));
	}

	function intent_link(){
	    
	    $udata = auth(2);
	    
	    if(!$udata){
	        die('<span style="color:#FF0000;">Error: Invalid Session. Refresh the Page to Continue.</span>');
	    } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Project ID.</span>');
	    } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Intent ID.</span>');
	    } elseif(!isset($_POST['target_id']) || intval($_POST['target_id'])<=0){
	        die('<span style="color:#FF0000;">Error: Missing target_id.</span>');
	    }
	    
	    //Validate Project ID:
	    $bs = $this->Db_model->b_fetch(array(
	        'b.b_id' => intval($_POST['b_id']),
	    ));
	    if(count($bs)<=0){
	        die('<span style="color:#FF0000;">Error: Invalid Project ID.</span>');
	    }
	    
	    //Validate outbound link:
	    $outbound_intents = $this->Db_model->c_fetch(array(
	        'c.c_id' => intval($_POST['target_id']),
	    ));
	    if(count($outbound_intents)<=0){
	        die('<span style="color:#FF0000;">Error: Invalid target_id.</span>');
	    }
	    
	    
	    //Validate inbound link:
	    $inbound_intents = $this->Db_model->c_fetch(array(
	        'c.c_id' => intval($_POST['pid']),
	    ));
	    if(count($inbound_intents)<=0){
	        die('<span style="color:#FF0000;">Error: Invalid PID.</span>');
	    }
	    
	    
	    //Check to make sure not a duplicate link:
	    $current_outbounds = $this->Db_model->cr_outbound_fetch(array(
	        'cr.cr_inbound_id' => intval($_POST['pid']),
	        'cr.cr_status >=' => 0,
	    ));
	    foreach($current_outbounds as $co){
	        if($co['cr_outbound_id']==intval($_POST['target_id'])){
	            //Oops, we already have this!
	            die('<script> alert("ERROR: Cannot create this link because it already exists."); </script>');
	        }
	    }
	    
	    
	    //Create Link:
	    $relation = $this->Db_model->cr_create(array(
	        'cr_creator_id' => $udata['u_id'],
	        'cr_inbound_id'  => intval($_POST['pid']),
	        'cr_outbound_id' => intval($_POST['target_id']),
	        'cr_outbound_rank' => 1 + $this->Db_model->max_value('v5_intent_links','cr_outbound_rank', array(
	            'cr_status >=' => 1,
                'c_status >=' => 1,
	            'cr_inbound_id' => intval($_POST['pid']),
	        )),
	    ));
	    
	    
	    //Log Engagement for New Intent Link:
	    $this->Db_model->e_create(array(
	        'e_initiator_u_id' => $udata['u_id'],
	        'e_message' => 'Linked intent ['.$outbound_intents[0]['c_objective'].'] as outbound of intent ['.$inbound_intents[0]['c_objective'].']',
	        'e_json' => array(
	            'input' => $_POST,
	            'before' => null,
	            'after' => $relation,
	        ),
	        'e_type_id' => 23, //New Intent Link
	        'e_b_id' => intval($_POST['b_id']),
	        'e_cr_id' => $relation['cr_id'],
	    ));
	    
	    
	    //Fetch full OUTBOUND link package:
	    $relations = $this->Db_model->cr_outbound_fetch(array(
	        'cr.cr_id' => $relation['cr_id'],
	    ));
	    
	    
	    //Return result:
	    echo echo_cr($_POST['b_id'],$relations[0],$_POST['next_level'],intval($_POST['pid']));
	}

	function migrate_step(){

        //Auth user and Load object:
        $udata = auth(2);
        if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Session. Login again to Continue.',
            ));
        } elseif(!isset($_POST['cr_id']) || intval($_POST['cr_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid cr_id',
            ));
        } elseif(!isset($_POST['c_id']) || intval($_POST['c_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid c_id',
            ));
        } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid b_id',
            ));
        } elseif(!isset($_POST['from_c_id']) || intval($_POST['from_c_id'])<=0) {
            echo_json(array(
                'status' => 0,
                'message' => 'Missing from_c_id',
            ));
        } elseif(!isset($_POST['to_c_id']) || intval($_POST['to_c_id'])<=0) {
            echo_json(array(
                'status' => 0,
                'message' => 'Missing to_c_id',
            ));
        } else {

            //Fetch all three intents to ensure they are all valid and use them for engagement logging:
            $subject = $this->Db_model->c_fetch(array(
                'c.c_id' => intval($_POST['c_id']),
            ));
            $from = $this->Db_model->c_fetch(array(
                'c.c_id' => intval($_POST['from_c_id']),
            ));
            $to = $this->Db_model->c_fetch(array(
                'c.c_id' => intval($_POST['to_c_id']),
            ));

            if(!isset($subject[0]) || !isset($from[0]) || !isset($to[0])){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Invalid intent IDs',
                ));
            } else {
                //Make the move:
                $this->Db_model->cr_update( intval($_POST['cr_id']) , array(
                    'cr_creator_id' => $udata['u_id'],
                    'cr_timestamp' => date("Y-m-d H:i:s"),
                    'cr_inbound_id' => intval($_POST['to_c_id']),
                    //No need to update sorting here as a separate JS function would call that within half a second after the move...
                ));

                //Log engagement:
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => $udata['u_id'],
                    'e_json' => array(
                        'post' => $_POST,
                    ),
                    'e_message' => '['.$subject[0]['c_objective'].'] was migrated from ['.$from[0]['c_objective'].'] to ['.$to[0]['c_objective'].']', //Message migrated
                    'e_type_id' => 50, //Message migrated
                    'e_c_id' => intval($_POST['c_id']),
                    'e_cr_id' => intval($_POST['cr_id']),
                    'e_b_id' => intval($_POST['b_id']),
                ));

                //Return success
                echo_json(array(
                    'status' => 1,
                    'message' => 'Move completed',
                ));
            }
        }
    }

	function intents_sort(){
	    //Auth user and Load object:
	    $udata = auth(2);
	    if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Session. Login again to Continue.',
            ));
	    } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid PID',
            ));
	    } elseif(!isset($_POST['new_sort']) || !is_array($_POST['new_sort']) || count($_POST['new_sort'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Nothing passed for sorting',
            ));
	    } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0) {
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project ID',
            ));
        } else {

            //Validate Parent intent:
            $inbound_intents = $this->Db_model->c_fetch(array(
                'c.c_id' => intval($_POST['pid']),
            ));
            if(count($inbound_intents)<=0){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Invalid PID',
                ));
            } else {

                //Fetch for the record:
                $outbounds_before = $this->Db_model->cr_outbound_fetch(array(
                    'cr.cr_inbound_id' => intval($_POST['pid']),
                    'cr.cr_status >=' => 0,
                ));

                //Update them all:
                foreach($_POST['new_sort'] as $rank=>$cr_id){
                    $this->Db_model->cr_update( intval($cr_id) , array(
                        'cr_creator_id' => $udata['u_id'],
                        'cr_timestamp' => date("Y-m-d H:i:s"),
                        'cr_outbound_rank' => intval($rank), //Might have decimal for DRAFTING Tasks/Steps
                    ));
                }

                //Fetch for the record:
                $outbounds_after = $this->Db_model->cr_outbound_fetch(array(
                    'cr.cr_inbound_id' => intval($_POST['pid']),
                    'cr.cr_status >=' => 0,
                ));

                //Log Engagement:
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => $udata['u_id'],
                    'e_message' => 'Sorted outbound intents for ['.$inbound_intents[0]['c_objective'].']',
                    'e_json' => array(
                        'input' => $_POST,
                        'before' => $outbounds_before,
                        'after' => $outbounds_after,
                    ),
                    'e_type_id' => 22, //Links Sorted
                    'e_b_id' => intval($_POST['b_id']),
                    'e_c_id' => intval($_POST['pid']),
                ));

                //Display message:
                echo_json(array(
                    'status' => 1,
                    'message' => '<i class="fa fa-check" aria-hidden="true"></i> Sorted',
                ));
            }
        }
	}
	
	/* ******************************
	 * i Messages
	 ****************************** */
	
	function detect_url(){
	    $urls = extract_urls($_POST['text']);
	    if(count($urls)>0){
	        //Fetch more details for the FIRST URL only and append to page:
	        $flash_url = $this->session->flashdata('first_url');
	        if(!$flash_url || !($flash_url==$urls[0])){
	            echo $urls[0].time();
	        }
	        //Set new flash data either way!
	        $this->session->set_flashdata('first_url', $urls[0]);
	    }
	}

    function dispatch_message(){

        //Auth user and check required variables:
        $udata = auth(2);

        if(!$udata){

            echo_json(array(
                'status' => 0,
                'message' => 'Session Expired',
            ));
            return false;

        } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0) {

            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Intent ID',
            ));
            return false;

        } elseif(!isset($_POST['depth']) || intval($_POST['depth'])<0) {

            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Depth',
            ));
            return false;

        } elseif(!isset($_POST['u_id']) || intval($_POST['u_id'])<=0) {

            echo_json(array(
                'status' => 0,
                'message' => 'Missing User ID',
            ));
            return false;

        } else {

            //All seems good, attempt dispatch:
            echo_json($this->Comm_model->foundation_message(array(
                'e_recipient_u_id' => intval($_POST['u_id']),
                'e_c_id' => intval($_POST['pid']),
                'depth' => intval($_POST['depth']),
                'e_b_id' => 0,
                'e_r_id' => 0,
            )));

        }

    }

    function message_attachment(){
	    
	    $udata = auth(2);
	    $file_limit_mb = $this->config->item('file_limit_mb');
	    if(!$udata){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Invalid Session. Refresh to Continue',
	        ));
	    } elseif(!isset($_POST['pid']) || !isset($_POST['b_id']) || !isset($_POST['i_status'])){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Missing intent data.',
	        ));
	    } elseif(!isset($_POST['upload_type']) || !in_array($_POST['upload_type'],array('file','drop'))){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Unknown upload type.',
	        ));
	    } elseif(!isset($_FILES[$_POST['upload_type']]['tmp_name']) || !isset($_FILES[$_POST['upload_type']]['type']) || !isset($_FILES[$_POST['upload_type']]['size'])){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Missing file.',
	        ));
	    } elseif($_FILES[$_POST['upload_type']]['size']>($file_limit_mb*1024*1024)){
	        
	        echo_json(array(
	            'status' => 0,
	            'message' => 'File is larger than '.$file_limit_mb.' MB.',
	        ));
	        
	    } else {

	        //Fetch Project:
            $bs = $this->Db_model->b_fetch(array(
                'b.b_id' => $_POST['b_id'],
            ));
            if(count($bs)<1){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Invalid Project ID.',
                ));
                return false;
            }
	        
	        //First save file locally:
	        $temp_local = "application/cache/temp_files/".$_FILES[$_POST['upload_type']]["name"];
	        move_uploaded_file( $_FILES[$_POST['upload_type']]['tmp_name'] , $temp_local );
	        
	        //Attempt to store in Cloud:
	        if(isset($_FILES[$_POST['upload_type']]['type']) && strlen($_FILES[$_POST['upload_type']]['type'])>0){
	            $mime = $_FILES[$_POST['upload_type']]['type'];
	        } else {
	            $mime = mime_content_type($temp_local);
	        }
	        
	        //Upload to S3:
	        $new_file_url = trim(save_file( $temp_local , $_FILES[$_POST['upload_type']] , true ));
	        
	        //What happened?
	        if(!$new_file_url){
	            
	            //Oops something went wrong:
	            echo_json(array(
	                'status' => 0,
	                'message' => 'Could not save to cloud!',
	            ));
	            
	        } else {
	            
	            //Detect file type:
	            $i_media_type = mime_type($mime);
	            
	            //Create Message:
	            $message = '/attach '.$i_media_type.':'.$new_file_url;
	            
	            //Create message:
	            $i = $this->Db_model->i_create(array(
	                'i_creator_id' => $udata['u_id'],
	                'i_c_id' => intval($_POST['pid']),
	                'i_media_type' => $i_media_type,
	                'i_message' => $message,
	                'i_url' => $new_file_url,
                    'i_status' => $_POST['i_status'],
	                'i_rank' => 1 + $this->Db_model->max_value('v5_messages','i_rank', array(
	                    'i_status' => $_POST['i_status'],
	                    'i_c_id' => $_POST['pid'],
	                )),
	            ));
	            
	            //Fetch full message:
	            $new_messages = $this->Db_model->i_fetch(array(
	                'i_id' => $i['i_id'],
	            ));
	            
	            //Log engagement:
	            $this->Db_model->e_create(array(
	                'e_initiator_u_id' => $udata['u_id'],
	                'e_json' => array(
	                    'post' => $_POST,
	                    'file' => $_FILES,
	                    'after' => $new_messages[0],
	                ),
	                'e_type_id' => 34, //Message added e_type_id=34
	                'e_i_id' => intval($new_messages[0]['i_id']),
	                'e_c_id' => intval($new_messages[0]['i_c_id']),
	                'e_b_id' => $bs[0]['b_id'],
	            ));


                //Does it have an attachment and a connected Facebook Page? If so, save the attachment:
                if($bs[0]['b_fp_id']>0 && in_array($i_media_type,array('image','audio','video','file'))){
                    //Log engagement for this to be done via a Cron Job:
                    $this->Db_model->e_create(array(
                        'e_initiator_u_id' => $udata['u_id'],
                        'e_type_id' => 83, //Message Facebook Sync e_type_id=83
                        'e_i_id' => intval($new_messages[0]['i_id']),
                        'e_c_id' => intval($new_messages[0]['i_c_id']),
                        'e_b_id' => $bs[0]['b_id'],
                        'e_fp_id' => $bs[0]['b_fp_id'],
                        'e_cron_job' => 0, //Job pending
                    ));
                }

	            
	            //Echo message:
	            echo_json(array(
	                'status' => 1,
	                'message' => echo_message( array_merge($new_messages[0], array(
	                    'e_b_id'=>$bs[0]['b_id'],
                        'e_recipient_u_id'=>$udata['u_id'],
                    )), $_POST['level']),
	            ));
	        }
	    }
	}

	function message_create(){

	    $udata = auth(2);
	    if(!$udata){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Session Expired. Login and Try again.',
	        ));
	    } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0 || !is_valid_intent($_POST['pid'])){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Invalid Step',
	        ));
	    } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Invalid Project',
	        ));
	    } else {

            $bs = $this->Db_model->b_fetch(array(
                'b.b_id' => $_POST['b_id'],
            ));
            if(count($bs)<1){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Invalid Project ID.',
                ));
                return false;
            }

	        //Make sure message is all good:
            $validation = message_validation($_POST['i_status'],$_POST['i_message']);

            if(!$validation['status']){

                //There was some sort of an error:
                echo_json($validation);

            } else {

                //Detect file type:
                if(count($validation['urls'])==1 && trim($validation['urls'][0])==trim($_POST['i_message'])){

                    //This message is a URL only, perform raw URL to file conversion
                    //This feature only available for newly created message, NOT in editing mode!
                    $mime = remote_mime($validation['urls'][0]);
                    $i_media_type = mime_type($mime);
                    if($i_media_type=='file'){
                        $i_media_type = 'text';
                    }

                } else {
                    //This channel is all text:
                    $i_media_type = 'text'; //Possible: text,image,video,audio,file
                }

                //Create Message:
                $i = $this->Db_model->i_create(array(
                    'i_creator_id' => $udata['u_id'],
                    'i_c_id' => intval($_POST['pid']),
                    'i_media_type' => $i_media_type,
                    'i_message' => trim($_POST['i_message']),
                    'i_url' => ( count($validation['urls'])==1 ? $validation['urls'][0] : null ),
                    'i_status' => $_POST['i_status'],
                    'i_rank' => 1 + $this->Db_model->max_value('v5_messages','i_rank', array(
                        'i_status' => $_POST['i_status'],
                        'i_c_id' => intval($_POST['pid']),
                    )),
                ));

                //Fetch full message:
                $new_messages = $this->Db_model->i_fetch(array(
                    'i_id' => $i['i_id'],
                ));

                //Log engagement:
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => $udata['u_id'],
                    'e_json' => array(
                        'input' => $_POST,
                        'after' => $new_messages[0],
                    ),
                    'e_type_id' => 34, //Message added
                    'e_i_id' => intval($new_messages[0]['i_id']),
                    'e_c_id' => intval($_POST['pid']),
                    'e_b_id' => $bs[0]['b_id'],
                ));

                //Print the challenge:
                echo_json(array(
                    'status' => 1,
                    'message' => echo_message(array_merge($new_messages[0],array(
                        'e_b_id'=>intval($_POST['b_id']),
                        'e_recipient_u_id'=>$udata['u_id'],
                    )), $_POST['level']),
                ));
            }
	    }   
	}
	
	function message_update(){
	    
	    //Auth user and Load object:
	    $udata = auth(2);
	    if(!$udata){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Invalid Session. Refresh.',
	        ));
	    } elseif(!isset($_POST['i_media_type'])){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Missing Type',
	        ));
	    } elseif(!isset($_POST['i_id']) || intval($_POST['i_id'])<=0){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Missing Message ID',
	        ));
	    } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0 || !is_valid_intent($_POST['pid'])){
	        echo_json(array(
	            'status' => 0,
	            'message' => 'Invalid Intent ID',
	        ));
	    } else {

            //Fetch Message:
            $messages = $this->Db_model->i_fetch(array(
                'i_id' => intval($_POST['i_id']),
                'i_status >' => 0,
            ));

            //Make sure message is all good:
            $validation = message_validation($_POST['i_status'],( isset($_POST['i_message']) ? $_POST['i_message'] : null ),$_POST['i_media_type']);

            if(!isset($messages[0])){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Message Not Found',
                ));
            } elseif(!$validation['status']){

                //There was some sort of an error:
                echo_json($validation);

            } else {

                //All good, lets move on:
                //Define what needs to be updated:
                $to_update = array(
                    'i_creator_id' => $udata['u_id'],
                    'i_timestamp' => date("Y-m-d H:i:s"),
                );

                //Is this a text message?
                if($_POST['i_media_type']=='text'){
                    $to_update['i_message'] = trim($_POST['i_message']);
                    $to_update['i_url'] = ( isset($validation['urls'][0]) ? $validation['urls'][0] : null );
                }

                if(!($_POST['initial_i_status']==$_POST['i_status'])){
                    //Change the status:
                    $to_update['i_status'] = $_POST['i_status'];
                    //Put it at the end of the new list:
                    $to_update['i_rank'] = 1 + $this->Db_model->max_value('v5_messages','i_rank', array(
                        'i_status' => $_POST['i_status'],
                        'i_c_id' => intval($_POST['pid']),
                    ));
                }

                //Now update the DB:
                $this->Db_model->i_update( intval($_POST['i_id']) , $to_update );

                //Re-fetch the message for display purposes:
                $new_messages = $this->Db_model->i_fetch(array(
                    'i_id' => intval($_POST['i_id']),
                ));

                //Log engagement:
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => $udata['u_id'],
                    'e_json' => array(
                        'input' => $_POST,
                        'before' => $messages[0],
                        'after' => $new_messages[0],
                    ),
                    'e_type_id' => 36, //Message edited
                    'e_i_id' => $messages[0]['i_id'],
                    'e_c_id' => intval($_POST['pid']),
                ));

                //Print the challenge:
                echo_json(array(
                    'status' => 1,
                    'message' => echo_i(array_merge($new_messages[0],array('e_recipient_u_id'=>$udata['u_id'])),$udata['u_fname']),
                    'new_status' => status_bible('i',$new_messages[0]['i_status'],1,'right'),
                    'success_icon' => '<span><i class="fa fa-check" aria-hidden="true"></i> Saved</span>',
                    'new_uploader' => echo_uploader($new_messages[0]), //If there is a person change...
                ));
            }
	    }
	}
	
	function message_delete(){
	    //Auth user and Load object:
	    $udata = auth(2);
	    
	    if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Session Expired. Login and try again',
            ));
	    } elseif(!isset($_POST['i_id']) || intval($_POST['i_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Message ID',
            ));
	    } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0 || !is_valid_intent($_POST['pid'])){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Intent ID',
            ));
	    } else {

            //Fetch Message:
            $messages = $this->Db_model->i_fetch(array(
                'i_id' => intval($_POST['i_id']),
                'i_status >' => 0, //Not deleted
            ));
            if(!isset($messages[0])){
                echo_json(array(
                    'status' => 0,
                    'message' => 'Message Not Found',
                ));
            } else {
                //Now update the DB:
                $this->Db_model->i_update( intval($_POST['i_id']) , array(
                    'i_creator_id' => $udata['u_id'],
                    'i_timestamp' => date("Y-m-d H:i:s"),
                    'i_status' => -1, //Deleted by instructor
                ));

                //Log engagement:
                $this->Db_model->e_create(array(
                    'e_initiator_u_id' => $udata['u_id'],
                    'e_json' => array(
                        'input' => $_POST,
                        'before' => $messages[0],
                    ),
                    'e_type_id' => 35, //Message deleted
                    'e_i_id' => intval($messages[0]['i_id']),
                    'e_c_id' => intval($_POST['pid']),
                ));

                echo_json(array(
                    'status' => 1,
                    'message' => '<span style="color:#222;"><i class="fa fa-trash" aria-hidden="true"></i> Deleted</span>',
                ));
            }
        }
	}

	function messages_sort(){
	    //Auth user and Load object:
	    $udata = auth(2);
	    if(!$udata){
            echo_json(array(
                'status' => 0,
                'message' => 'Session Expired. Login and try again',
            ));
	    } elseif(!isset($_POST['new_sort']) || !is_array($_POST['new_sort']) || count($_POST['new_sort'])<=0){
            echo_json(array(
                'status' => 1, //Do not treat this as error as it could happen in moving Messages between types
                'message' => 'There was nothing to sort',
            ));
	    } elseif(!isset($_POST['b_id']) || intval($_POST['b_id'])<=0){
            echo_json(array(
                'status' => 0,
                'message' => 'Missing Project ID',
            ));
	    } elseif(!isset($_POST['pid']) || intval($_POST['pid'])<=0 || !is_valid_intent($_POST['pid'])){
            echo_json(array(
                'status' => 0,
                'message' => 'Invalid Intent ID',
            ));
	    } else {

            //Update them all:
            $sort_count = 0;
            foreach($_POST['new_sort'] as $i_rank=>$i_id){
                if(intval($i_id)>0){
                    $sort_count++;
                    $this->Db_model->i_update( $i_id , array(
                        'i_rank' => intval($i_rank),
                    ));
                }
            }

            //Log engagement:
            $this->Db_model->e_create(array(
                'e_initiator_u_id' => $udata['u_id'],
                'e_json' => $_POST,
                'e_type_id' => 39, //Messages sorted
                'e_c_id' => intval($_POST['pid']),
                'e_b_id' => intval($_POST['b_id']),
            ));

            echo_json(array(
                'status' => 1,
                'message' => $sort_count.' Sorted', //Does not matter as its currently not displayed in UI
            ));
        }
	}
	
}
