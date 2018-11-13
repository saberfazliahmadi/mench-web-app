<?php

$k_outs = $this->Db_model->k_fetch(array(
    'k_cr_id' => $cr_id, //Active subscriptions only
    'k_status >=' => 2, //Real completion [You can remove this to see all submissions with all statuses]
    //We are fetching with any k_status just to see what is available/possible from here
), array('w','w_c','w_u'), array('k_status'=>'ASC'), 0);

//Fetch objects
$current_status = -999; //This would keep going higher as we print each heather...
echo '<div class="list-group list-grey maxout">';
foreach($k_outs as $k){
    if($k['k_status']>$current_status){
        //Print header:
        echo '<h3 style="margin-top:15px;">'.echo_status('k_status',$k['k_status']).'</h3>';
        //Update pointer:
        $current_status = $k['k_status'];
    }
    echo echo_k_console($k);
}
echo '</div>';

?>