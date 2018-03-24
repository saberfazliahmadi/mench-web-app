
<script>
    $( document ).ready(function() {
        $("#classes_start").countdowntimer({
            startDate : "<?= date('Y/m/d H:i:s'); ?>",
            dateAndTime : "<?= date('Y/m/d H:i:s' , strtotime('next monday')); ?>",
            size : "lg",
            regexpMatchFormat: "([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})",
            regexpReplaceWith: "<b>$1</b><sup>Days</sup><b>$2</b><sup>H</sup><b>$3</b><sup>M</sup><b>$4</b><sup>S</sup>"
        });
    });
</script>


<!-- <h1>Achieve Your Goals</h1> -->
<h1>Bootcamps by Industry Experts</h1>
<p style="font-size: 1.3em;">Learn in-demand skills by completing 7-Day Bootcamps designed by Industry Experts.</p>
<p style="font-size: 1.3em;">New Classes start every Monday, in <span id="classes_start"></span></p>
<br />


<!-- <div class="col-sm-4"><?php // echo tree_menu(4793,array(4793)) ?></div> -->
<?php

//Fetch projects:
$bs = $this->Db_model->remix_bs(array(
    'b.b_status' => 3,
    'b.b_fp_id >' => 0,
    'b.b_old_format' => 0,
),array('ba','ihm'));

echo '<div class="row">';
foreach($bs as $count=>$b){

    if($count>0 && fmod($count,3)==0){
        echo '</div><div class="row">';
    }

    echo '<div class="col-md-4">
<div class="card card-product">
<div class="card-image"><a href="/'.$b['b_url_key'].'">'.$b['c__header_media'].'</a></div>
<div class="card-content">';

    if($b['b_difficulty_level']>0){
        echo '<h6 class="category text-muted">'.status_bible('df',$b['b_difficulty_level'],0,'top').'</h6>';
    }
    echo '<h4 class="card-title" style="font-size: 1.4em; line-height: 110%; margin:15px 0 12px 0;"><a href="/'.$b['b_url_key'].'">'.$b['c_objective'].'</a></h4>';
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
            <h4>'.echo_price($b).'</h4>
        </div>
        <div class="stats"><span data-toggle="tooltip" data-placement="top" title="Complete '.$b['c__tasks_count'].' Task'.show_s($b['c__tasks_count']).' totalling '.echo_hours($b['c__estimated_hours'],true).' anytime during the week" style="border-bottom: 1px dotted #777; padding-bottom:1px;"><i class="fa fa-clock-o" aria-hidden="true"></i> '.echo_hours($b['c__estimated_hours'],true).'</span></div>
    </div>';

    echo '</div>
</div>
</div>';
}

echo '</div>';
?>


</div>
</div>