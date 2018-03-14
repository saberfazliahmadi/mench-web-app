<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Front extends CI_Controller {
	
	function __construct() {
		parent::__construct();
		$this->output->enable_profiler(FALSE);
	}

    function ping(){
        echo_json(array('status'=>'success'));
    }
	
	function index(){		
		//Load home page:
		$this->load->view('front/shared/f_header' , array(
		    'title' => '7-Day Projects Lead by Industry Experts',
		));
		$this->load->view('front/project/marketplace' , array(
		    'bs' => $this->Db_model->remix_projects(array(
                'b.b_status' => 3,
            )),
        ));
		$this->load->view('front/shared/f_footer');
	}
	
	function login(){
	    //Check to see if they are already logged in?
	    $udata = $this->session->userdata('user');
	    if(isset($udata['u_id']) && $udata['u_status']>=2){
	        //Lead instructor and above, go to console:
	        redirect_message('/console');
	    }
	    
		$this->load->view('front/shared/f_header' , array(
		    'title' => 'Login',
		));
		$this->load->view('front/login');
		$this->load->view('front/shared/f_footer');
	}
	
	function terms(){
		$this->load->view('front/shared/f_header' , array(
		    'title' => 'Terms & Privacy Policy',
		));
		$this->load->view('front/terms');
		$this->load->view('front/shared/f_footer');
	}
	
	function contact(){
		$this->load->view('front/shared/f_header' , array(
		    'title' => 'Contact Us',
		));
		$this->load->view('front/contact');
		$this->load->view('front/shared/f_footer');
	}
	
	function ses(){
		//For admins
		echo exec('whoami');
		print_r($this->session->all_userdata());
		echo phpinfo();
	}
	
	
	/* ******************************
	 * Pitch Pages
	 ****************************** */


	function instructors(){
	    $this->load->view('front/shared/f_header' , array(
            'title' => 'Guide Students to Success',
            'landing_page' => 'front/splash/instructors_why',
	    ));
	    $this->load->view('front/instructors');
	    $this->load->view('front/shared/f_footer');
	}
	
	
	/* ******************************
	 * Project PUBLIC
	 ****************************** */


    function affiliate_click($b_id,$u_id=0,$goto_apply=0){
	    //DEPRECATED: Just keeping for Jason Cannon's Link to His Bootcamp
        $bs = $this->Db_model->b_fetch(array(
            'b.b_id' => $b_id,
        ));
        if(count($bs)>0){
            //Lets redirect to Page:
            redirect_message('/'.$bs[0]['b_url_key'].( $goto_apply ? '/apply' : '' ) );
        } else {
            //Invalid Bootcamp ID
            redirect_message('/','<div class="alert alert-danger" role="alert">Invalid Project ID.</div>');
        }
    }
	
	
	function project_load($b_url_key,$r_id=null){
	    
	    //Fetch data:
	    $udata = $this->session->userdata('user');
	    $bs = $this->Db_model->remix_projects(array(
	        'LOWER(b.b_url_key)' => strtolower($b_url_key),
	    ));

        //Validate Project:
        if(!isset($bs[0])){
            //Invalid key, redirect back:
            redirect_message('/','<div class="alert alert-danger" role="alert">Invalid Project URL.</div>');
        } elseif($bs[0]['b_status']<2 && (!isset($udata['u_status']) || $udata['u_status']<2)){
            redirect_message('/','<div class="alert alert-danger" role="alert">Project is not published yet.</div>');
        } elseif($bs[0]['b_fp_id']<=0){
            redirect_message('/','<div class="alert alert-danger" role="alert">Project not connected to a Facebook Page yet.</div>');
        } elseif(!(strcmp($bs[0]['b_url_key'], $b_url_key)==0)){
            //URL Case sensitivity redirect:
            redirect_message('/'.$bs[0]['b_url_key']);
        }


	    //Validate Class:
	    $b = $bs[0];
	    $focus_class = filter_class($b['c__classes'],$r_id);
	    if(!$focus_class){
	        if(isset($udata['u_status']) && $udata['u_status']>=2){
	            //This is an admin, get them to the editing page:
                redirect_message('/','<div class="alert alert-danger" role="alert">Error: '.( $r_id ? 'Class is expired.' : 'You must <a href="/console/'.$b['b_id'].'/classes"><b><u>Create A Published Class</u></b></a> before loading the landing page.' ).'</div>');
            } else {
	            //This is a user, give them a standard error:
                redirect_message('/','<div class="alert alert-danger" role="alert">Error: '.( $r_id ? 'Class is expired.' : 'Did not find an active class for this Project.' ).'</div>');
            }
	    }


	    //Load home page:
	    $this->load->view('front/shared/f_header' , array(
	        'title' => $b['c_objective'].' - Starting '.time_format($focus_class['r_start_date'],4),
	        'message' => ( $b['b_status']<2 ? '<div class="alert alert-danger" role="alert"><span><i class="fa fa-eye-slash" aria-hidden="true"></i> INSTRUCTOR VIEW ONLY:</span>You can view this Project only because you are logged-in as a Mench instructor.<br />This Project is hidden from the public until published live.</div>' : null ),
	        'b_fb_pixel_id' => $b['b_fb_pixel_id'], //Will insert pixel code in header
            'canonical' => 'https://mench.com/'.$b['b_url_key'].( $r_id ? '/'.$r_id : '' ), //Would set this in the <head> for SEO purposes
	    ));
	    $this->load->view('front/project/landing_page' , array(
	        'b' => $b,
	        'focus_class' => $focus_class,
	    ));
	    $this->load->view('front/shared/f_footer');
	}
	
	
	function project_apply($b_url_key,$r_id=null){
	    //The start of the funnel for email, first name & last name

        //Fetch data:
        $udata = $this->session->userdata('user');
        $bs = $this->Db_model->remix_projects(array(
            'LOWER(b.b_url_key)' => strtolower($b_url_key),
        ));

        //Validate Project:
        if(!isset($bs[0])){
            //Invalid key, redirect back:
            redirect_message('/','<div class="alert alert-danger" role="alert">Invalid Project URL.</div>');
        } elseif($bs[0]['b_status']<2){
            //Here we don't even let instructors move forward to apply!
            redirect_message('/','<div class="alert alert-danger" role="alert">Admission starts after Project is published live.</div>');
        } elseif($bs[0]['b_fp_id']<=0){
            redirect_message('/','<div class="alert alert-danger" role="alert">Project not connected to a Facebook Page yet.</div>');
        }
	    
	    //Validate Class ID that it's still the latest:
	    $b = $bs[0];
	    
	    //Lets figure out how many active classes there are!
	    $active_classes = array();
	    foreach($b['c__classes'] as $class){
	        if(filter_class(array($class),$class['r_id'])){
	            array_push($active_classes,$class);
	        }
	    }
	    
	    if(count($active_classes)<1){
	        
	        //Ooops, no active classes!
	        redirect_message('/'.$b_url_key ,'<div class="alert alert-danger" role="alert">No active classes found for this Project.</div>');
	        
	    } elseif(!$r_id && count($active_classes)>1){
	        
	        //Let the students choose which class they like to join:
	        $data = array(
	            'b' => $b,
	            'active_classes' => $active_classes,
	            'title' => 'Join '.$b['c_objective'],
                'canonical' => 'https://mench.com/'.$b['b_url_key'].( $r_id ? '/'.$r_id : '' ), //Would set this in the <head> for SEO purposes
	        );

	        //Load apply page:
	        $this->load->view('front/shared/p_header' , $data);
	        $this->load->view('front/project/choose_class' , $data); //TODO Build this
	        $this->load->view('front/shared/p_footer');
	        
	    } else {
	        
	        //Match the class and move on:
	        $focus_class = filter_class($b['c__classes'],$r_id);
	        if(!$focus_class){
	            //Invalid class ID, redirect back:
	            redirect_message('/'.$b_url_key ,'<div class="alert alert-danger" role="alert">Class is no longer active.</div>');
	        }

	        $data = array(
	            'title' => 'Join '.$b['c_objective'].' - Starting '.time_format($focus_class['r_start_date'],4),
	            'focus_class' => $focus_class,
	            'b_fb_pixel_id' => $b['b_fb_pixel_id'], //Will insert pixel code in header
	        );
	        
	        //Load apply page:
	        $this->load->view('front/shared/p_header' , $data);
	        $this->load->view('front/project/apply' , $data);
	        $this->load->view('front/shared/p_footer');

	    }
	}
}
