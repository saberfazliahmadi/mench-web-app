<?php 
//Calculate office hours:
$office_hours = unserialize($focus_class['r_live_office_hours']);
$days = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday');
$office_hours_ui = array();


$total_office_hours = 0;
if(isset($office_hours) && is_array($office_hours)){
    foreach ($office_hours as $key=>$oa){
        $string = null;
        if(isset($oa['periods']) && count($oa['periods'])>0){
            //Yes we have somehours for this day:
            foreach($oa['periods'] as $period){
                if(!$string){
                    $string = '<span style="width:100px; display:inline-block;font-weight:bold;">'.$days[$key].'</span>';
                } else {
                    $string .= ' & ';
                }
                $string .= $period[0].' - '.$period[1];
                
                //Calculate hours for this period:
                $total_office_hours += hourformat($period[1]) - hourformat($period[0]);
            }
        }
        if($string){
            $string .= ' PST';
            array_push($office_hours_ui,$string);
        }
    }
}


//See if this project has multiple active Classes:
$available_classes = 0;
$class_selection = '<h4 id="available_classes"><i class="fa fa-calendar" aria-hidden="true"></i> Available Classes</h4>';
$class_selection .= '<div id="class_list" class="list-group" style="max-width:none !important;">';
$b['c__classes'] = array_reverse($b['c__classes']);

foreach($b['c__classes'] as $class){
    if($class['r_status']==1 && !date_is_past($class['r_start_date'])){
        $available_classes++;
        if($class['r_id']==$focus_class['r_id']){
            $class_selection .= '<li class="list-group-item" style="background-color:#f5f5f5;">';
        } else {
            $class_selection .= '<a href="/'.$b['b_url_key'].'/'.$class['r_id'].'" class="list-group-item">';
            $class_selection .= '<span class="pull-right"><span class="badge badge-primary"><i class="fa fa-chevron-right" aria-hidden="true"></i></span></span>';
        }
        
        
        $class_selection .= '<i class="fa fa-calendar" aria-hidden="true"></i> <b>'.time_format($class['r_start_date'],2).'</b> &nbsp; ';
        $class_selection .= '<i class="fa fa-usd" aria-hidden="true"></i> '.(strlen($class['r_usd_price'])>0 ? number_format($class['r_usd_price']) : 'FREE' ).' &nbsp; ';
        if($class['r_max_students']>0){
            $class_selection .= '<i class="fa fa-user" aria-hidden="true"></i> '.$class['r_max_students'].' Seats';
            if($class['r__current_admissions']>=$class['r_max_students']){
                //Class is full:
                $class_selection .= ' <span style="color:#FF0000;">(FULL, '.($class['r__current_admissions']-$class['r_max_students']).' in Waiting List)</span>';
            } elseif(($class['r__current_admissions']/$class['r_max_students'])>0.66){
                //Running low on space:
                $class_selection .= ' <span style="color:#FF0000;">('.($class['r_max_students']-$class['r__current_admissions']).' Remaining)</span>';
            }
            $class_selection .= ' &nbsp; ';
        }
        if($class['r_id']==$focus_class['r_id']){
            $class_selection .= '<span class="label label-default" style="background-color:#fedd16; color:#000;">CURRENTLY VIEWING</span>';
        }
        $class_selection .= ($class['r_id']==$focus_class['r_id'] ? '</li>' : '</a>' );
        
        //Do not show more than the next 7 Classes:
        if($available_classes>=7){
            break;
        }
    }
}
$class_selection .= '</div>';
$class_selection .= '<hr />';
?>

<style>
    .msg { font-size:18px !important; font-weight:300 !important;}
    .msg a { max-width: none; }
</style>
<script>

function choose_r(){
	//Flash border color:
	$('html,body').animate({
		scrollTop: $('#available_classes').offset().top - 65
	}, 150);
}

function toggleview(object_key){
	if($('#'+object_key+' .pointer').hasClass('fa-caret-right')){
		//Opening an item!
		//Make sure all other items are closed:
		$('.pointer').removeClass('fa-caret-down').addClass('fa-caret-right');
		$('.toggleview').hide();
		//Now show this item:
		$('#'+object_key+' .pointer').removeClass('fa-caret-right').addClass('fa-caret-down');
		$('.'+object_key).fadeIn();
		//Now adjust screen view port:
		$('html,body').animate({
			scrollTop: $('#'+object_key).offset().top - 65
		}, 150);
		
	} else if($('#'+object_key+' .pointer').hasClass('fa-caret-down')){
		//Close this specific item:
		$('#'+object_key+' .pointer').removeClass('fa-caret-down').addClass('fa-caret-right');
		$('.'+object_key).hide();
	}
}

$( document ).ready(function() {
	$("#reg1, #reg2, #reg3").countdowntimer({
		startDate : "<?php echo date('Y/m/d H:i:s'); ?>",
        dateAndTime : "<?php echo date('Y/m/d' , time_format($focus_class['r_start_date'],3,-1)); ?> 23:59:59",
		size : "lg",
		regexpMatchFormat: "([0-9]{1,3}):([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})",
      		regexpReplaceWith: "<b>$1</b><sup>Days</sup><b>$2</b><sup>H</sup><b>$3</b><sup>M</sup><b>$4</b><sup>S</sup>"
	});
});

</script>


<h1 style="margin-bottom:30px;"><?= $b['c_objective'] ?></h1>

<div class="row" id="landing_page">

	<div class="col-md-4">
        <div id="sidebar">
        	
        	<h3 style="margin-top:0;">Project Snapshot</h3>
        	
            <ul style="list-style:none; margin-left:0; padding:5px 10px; background-color:#EFEFEF; border-radius:5px;">
                <li>Duration: <b>7 Days</b></li>
                <li>Dates: <b><?= time_format($focus_class['r_start_date'],1) ?> - <?= time_format($focus_class['r_start_date'],1,(7*24*3600-60)) ?></b></li>
            	<li>Commitment: <b><?= echo_hours($b['c__estimated_hours']/7) ?> Per Day</b></li>
            	
            	<?php if($total_office_hours>0){ ?>
            	<li>Group Calls: <b><?= echo_hours($total_office_hours) ?> Per Week</b></li>
            	<?php } ?>

                <li>Price: <b><?= echo_price($focus_class['r_usd_price']).( $focus_class['r_usd_price']>0 ? ' <span style="font-weight:300; font-size: 0.9em;">(<a href="https://support.mench.co/hc/en-us/articles/115002080031">Mench Guarantee</a>)</span>' : '' ); ?></b></li>
            	
            	<?php if($focus_class['r_max_students']>0){ ?>
            		<li>Availability: <b><?= $focus_class['r_max_students'] ?> Seats</b>
                        <?php
                        if($focus_class['r__current_admissions']>=$focus_class['r_max_students']){
                            //Class is full:
                            echo ' <div style="color:#FF0000;">(FULL, '.($focus_class['r__current_admissions']-$focus_class['r_max_students']).' in Waiting List)</div>';
                        } elseif(($focus_class['r__current_admissions']/$focus_class['r_max_students'])>0.66){
                            //Running low on space:
                            echo ' <span style="color:#FF0000;">('.($focus_class['r_max_students']-$focus_class['r__current_admissions']).' Remaining)</span>';
                        }
                        ?>
                    </li>
            	<?php } ?>

            </ul>
            
            <div style="padding:10px 0 30px; text-align:center;">
                <div class="btn btn-primary btn-round countdown"><span id="reg1"></span></div>
            	<a href="/<?= $b['b_url_key'] ?>/apply/<?= $focus_class['r_id'] ?>" class="btn btn-primary btn-round">Join Class of <u><?= time_format($focus_class['r_start_date'],4) ?></u> &nbsp;<i class="fa fa-arrow-right" aria-hidden="true"></i></a>
            	<?= ( $available_classes>1 ? '<div>or <a href="javascript:choose_r();"><u>Choose Another Class</u></a></div>' : '' ) ?>
            </div>
        </div>
    </div>
    
    
    <div class="col-md-8">
    
        <?php
        foreach($b['c__messages'] as $i){
            if($i['i_status']==1){
                //Publish to Landing Page!
                echo echo_i($i);
            }
        }
        ?>


        <h3>Skills You Will Gain</h3>
        <div id="b_transformations"><?= ( strlen($b['b_transformations'])>0 ? '<ol><li>'.join('</li><li>',json_decode($b['b_transformations'])).'</li></ol>' : 'Not Set Yet' ) ?></div>

        <h3>Target Audience</h3>
        <div id="b_target_audience"><?= ( strlen($b['b_target_audience'])>0 ? '<ol><li>'.join('</li><li>',json_decode($b['b_target_audience'])).'</li></ol>' : 'Not Set Yet' ) ?></div>

        <h3>Prerequisites</h3>
        <?php $pre_req_array = prep_prerequisites($b); ?>
        <div id="b_prerequisites"><?= ( count($pre_req_array)>0 /* Should always be true! */ ? '<ol><li>'.join('</li><li>',$pre_req_array).'</li></ol>' : 'None' ) ?></div>


        <h3>Action Plan</h3>
        <div id="c_goals_list">
        <?php
        foreach($b['c__child_intents'] as $sprint){
            if($sprint['c_status']<1){
                continue;
            }
            echo '<div id="c_'.$sprint['c_id'].'">';
            echo '<h4><a href="javascript:toggleview(\'c_'.$sprint['c_id'].'\');" style="font-weight: normal;"><i class="pointer fa fa-caret-right" aria-hidden="true"></i> Task '.$sprint['cr_outbound_rank'].': '.$sprint['c_objective'].'</a></h4>';
                echo '<div class="toggleview c_'.$sprint['c_id'].'" style="display:none;">';

                    //Display all Active Steps:
                    if(count($sprint['c__child_intents'])>0){
                        echo '<ul>';
                        foreach($sprint['c__child_intents'] as $step){
                            if($step['c_status']<1){
                                continue; //Not published yet
                            }
                            echo '<li>'.$step['c_objective'].'</li>';
                        }
                        echo '</ul>';
                    }

                    echo '<div class="title-sub">';
                    if($sprint['c__estimated_hours']>0){
                        echo str_replace('title-sub','',echo_time($sprint['c__estimated_hours'],1)).' &nbsp; ';
                    }
                    echo '<i class="fa fa-calendar" aria-hidden="true"></i> Complete By '.time_format($focus_class['r_start_date'],2,(7*24*3600-60));
                    echo '</div>';
                echo '</div>';
            echo '</div>';
        }
        ?>
        </div>
    		
    		
    		
    		
    		<h3>1-on-1 Support</h3>

            <h4><i class="fa fa-comments" aria-hidden="true"></i> Chat Response Time</h4>
            <p>This Project offers live chat and email support during the 1-Week period. You can ask <b>unlimited questions</b> from your instructor.</p>
            <hr />


            <?php
    		if(count($office_hours_ui)>0 || $total_office_hours>0){
    		    echo '<h4><i class="fa fa-podcast" aria-hidden="true"></i> Group Calls</h4>';
    		    echo '<p>You can access <b>'.echo_hours($total_office_hours).'</b> of live group support during these time-slots:</p>';
    		    echo '<ul style="list-style:none; margin-left:-20px;">';
    		    foreach($office_hours_ui as $oa_ui){
    		        echo '<li>'.$oa_ui.'</li>';
    		    }
    		    echo '</ul>';
    		    echo '<hr />';
    		}
    		?>



    		
    		
    		<h3>Instructors</h3>
    		<?php
            $admin_count = 0;
            $leader_fname = '';
            foreach($b['b__admins'] as $admin){
                if($admin['ba_team_display']!=='t'){
                    continue;
                }
                if($admin_count>0){
                    echo '<hr />';
                }
                
                if($admin['ba_status']==3){
                    $leader_fname = $admin['u_fname'];
                }
                echo '<h4 class="userheader"><img src="'.$admin['u_image_url'].'" /> '.$admin['u_fname'].' '.$admin['u_lname'].'<span><img src="/img/flags/'.strtolower($admin['u_country_code']).'.png" class="flag" style="margin-top:-4px;" /> '.$admin['u_current_city'].'</span></h4>';
                echo '<p id="u_bio">'.$admin['u_bio'].'</p>';
                
                //Any languages other than English?
                if(strlen($admin['u_language'])>0 && $admin['u_language']!=='en'){
                    $all_languages = $this->config->item('languages');
                    //They know more than enligh!
                    $langs = explode(',',$admin['u_language']);
                    echo '<i class="fa fa-language ic-lrg" aria-hidden="true"></i>Fluent in ';
                    $count = 0;
                    foreach($langs as $lang){
                        if($count>0){
                            echo ', ';
                        }
                        echo $all_languages[$lang];
                        $count++;
                    }
                }
                
                //Public profiles:
                echo '<div class="public-profiles" style="margin-top:10px;">';
                if(strlen($admin['u_website_url'])>0){
                    echo '<a href="'.$admin['u_website_url'].'" data-toggle="tooltip" title="Visit Website" target="_blank"><i class="fa fa-chrome" aria-hidden="true"></i></a>';
                }
                $u_social_account = $this->config->item('u_social_account');
                foreach($u_social_account as $sa_key=>$sa){
                    if(strlen($admin[$sa_key])>0){
                        echo '<a href="'.$sa['sa_prefix'].$admin[$sa_key].$sa['sa_postfix'].'" data-toggle="tooltip" title="'.$sa['sa_name'].'" target="_blank">'.$sa['sa_icon'].'</a>';
                    }
                }
                echo '</div>';
                
                $admin_count++;
            }
            ?>
    
    
    

    		<h3>Admission</h3>
    		
    		<?php 
    		if($available_classes>1){
    		    echo $class_selection;
    		}
    		?>
    		
    		<h4><i class="fa fa-clock-o" aria-hidden="true"></i> Timeline</h4>
    		<ul style="list-style:none; margin-left:-30px;">
    			<li>Admission Ends <b><?= time_format($focus_class['r_start_date'],2,-1) ?> 11:59pm PST</b> (Ends in <span id="reg2"></span>)</li>
    			<li>Class Starts <b><?= time_format($focus_class['r_start_date'],2) ?></b></li>
    			<li>Class Duration is <b><?= $b['c__tasks_count'] .' Task'.show_s($b['c__tasks_count']) ?></b></li>
    			<li>Class Ends <b><?= time_format($focus_class['r_start_date'],2,(7*24*3600-60)) ?></b></li>
    		</ul>
    		<hr />
    		
    		
    		
    		<?php if(strlen($b['b_completion_prizes'])>0){ ?>
                <h4><i class="fa fa-gift" aria-hidden="true"></i> Completion Award<?= show_s(count(json_decode($b['b_completion_prizes']))) ?></h4>
                <div id="r_completion_prizes"><?= '<ol><li>'.join('</li><li>',json_decode($b['b_completion_prizes'])).'</li></ol>' ?></div>
                <p>Awarded for completing all Tasks by the end of the 7-Day project.</p>
                <hr />
    		<?php } ?>


            <p>Ready to unleash your full potential?</p>
            <p>Admission ends in:</p>
    		
    </div>
</div>


<div style="padding:20px 0 30px; text-align:center;">
	<div class="btn btn-primary btn-round countdown"><span id="reg3"></span></div>
    <br />
    <a href="/<?= $b['b_url_key'] ?>/apply/<?= $focus_class['r_id'] ?>" class="btn btn-primary btn-round">Join Class of <u><?= time_format($focus_class['r_start_date'],4) ?></u> &nbsp;<i class="fa fa-arrow-right" aria-hidden="true"></i></a>
	<?= ( $available_classes>1 ? '<div>or <a href="javascript:choose_r();"><u>Choose Another Class</u></a></div>' : '' ) ?>
</div>


</div>
</div>


<div>
<div class="container">
	
<?php $this->load->view('front/shared/projects_include'); ?>
<br /><br />


