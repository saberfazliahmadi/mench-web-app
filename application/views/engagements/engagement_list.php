<?php

//Fetch data:
$engagements = $this->Db_model->e_fetch(array(
    '(e_child_u_id = '.$u_id.' OR e_parent_u_id = '.$u_id.')' => null,
    '(e_parent_c_id NOT IN ('.join(',', $this->config->item('exclude_es')).'))' => null,
), (is_dev() ? 20 : 100));

//Show this data:
//Fetch objects
echo '<div class="list-group list-grey" style="margin:-14px -5px -16px;">';
foreach($engagements as $e){
    echo echo_e($e);
}
echo '</div>';
?>