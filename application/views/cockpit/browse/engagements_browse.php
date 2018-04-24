<?php
/**
 * Created by PhpStorm.
 * User: shervinenayati
 * Date: 2018-04-13
 * Time: 9:38 AM
 */

//Define engagement filters:
$engagement_references = $this->config->item('engagement_references');
$e_type_id = $this->Db_model->a_fetch();

$engagement_filters = array(
    'e_type_id' => 'All Engagements',
    'e_id' => 'Engagement ID',
    'e_u_id' => 'User ID',
    'e_b_id' => 'Bootcamp ID',
    'e_r_id' => 'Class ID',
    'e_c_id' => 'Intent ID',
    'e_fp_id' => 'FB Page ID',
);

$match_columns = array();
foreach($engagement_filters as $key=>$value){
    if(isset($_GET[$key])){
        if($key=='e_u_id'){
            //We need to look for both inititors and recipients:
            if(substr_count($_GET[$key],',')>0){
                //This is multiple IDs:
                $match_columns['(e_recipient_u_id IN ('.$_GET[$key].') OR e_initiator_u_id IN ('.$_GET[$key].'))'] = null;
            } elseif(intval($_GET[$key])>0) {
                $match_columns['(e_recipient_u_id = '.$_GET[$key].' OR e_initiator_u_id = '.$_GET[$key].')'] = null;
            }
        } else {
            if(substr_count($_GET[$key],',')>0){
                //This is multiple IDs:
                $match_columns[$key.' IN ('.$_GET[$key].')'] = null;
            } elseif(intval($_GET[$key])>0) {
                $match_columns[$key] = intval($_GET[$key]);
            }
        }
    }
}

//Fetch engagements with possible filters:
$engagements = $this->Db_model->e_fetch($match_columns,(is_dev() ? 20 : 100));

?>

    <style>
        table, tr, td, th { text-align:left !important; font-size:14px; cursor:default !important; line-height:120% !important; }
        th { font-weight:bold !important; }
        td { padding:5px 0 !important; }
    </style>

<?php
//Display filters:
echo '<form action="" method="GET">';
echo '<table class="table table-condensed"><tr>';
foreach($engagement_filters as $key=>$value){
    echo '<td><div style="padding-right:5px;">';
    if(isset(${$key})){ //We have a list to show:
        echo '<select name="'.$key.'" class="border" style="width:160px;">';
        echo '<option value="0">'.$value.'</option>';
        foreach(${$key} as $key2=>$value2){
            echo '<option value="'.$key2.'" '.((isset($_GET[$key]) && intval($_GET[$key])==$key2)?'selected="selected"':'').'>'.$value2.'</option>';
        }
        echo '</select>';
    } else {
        //show text input
        echo '<input type="text" name="'.$key.'" placeholder="'.$value.'" value="'.((isset($_GET[$key]))?$_GET[$key]:'').'" class="form-control border">';
    }
    echo '</div></td>';
}
echo '<td><input type="submit" class="btn btn-sm btn-primary" value="Apply" /></td>';
echo '</tr></table>';
echo '</form>';
?>

    <table class="table table-condensed table-striped">
        <thead>
        <tr>
            <th style="width:120px;">Time</th>
            <th style="width:120px;">Action</th>
            <th><div style="padding-left:10px;">Message</div></th>
            <th style="width:300px;">References</th>
            <th style="width:30px; text-align:center !important;">&nbsp;</th>
        </tr>
        </thead>
        <tbody>
        <?php
        //Fetch objects
        foreach($engagements as $e){
            echo '<tr>';
            echo '<td><span aria-hidden="true" data-toggle="tooltip" data-placement="right" title="'.date("Y-m-d H:i:s",strtotime($e['e_timestamp'])).' Engagement #'.$e['e_id'].'" class="underdot">'.time_format($e['e_timestamp']).'</span></td>';
            echo '<td><span data-toggle="tooltip" title="'.$e['a_desc'].' (Type #'.$e['a_id'].')" aria-hidden="true" data-placement="right" class="underdot">'.$e['a_name'].'</span></td>';

            //Do we have a message?
            if(strlen($e['e_message'])>0){
                $e['e_message'] = format_e_message($e['e_message']);
            } elseif($e['e_i_id']>0){
                //Fetch message conent:
                $matching_messages = $this->Db_model->i_fetch(array(
                    'i_id' => $e['e_i_id'],
                ));
                if(count($matching_messages)>0){
                    $e['e_message'] = echo_i($matching_messages[0]);
                }
            }

            echo '<td><div style="max-width:300px; padding-left:10px;">'.$e['e_message'].( in_array($e['e_cron_job'],array(0,-2)) ? '<div style="color:#008000;"><i class="fa fa-spinner fa-spin fa-3x fa-fw" style="font-size:14px;"></i> Processing...</div>' : '' ).'</div></td>';
            echo '<td>';

            //Lets go through all references to see what is there:
            foreach($engagement_references as $engagement_field=>$er){
                if(intval($e[$engagement_field])>0){
                    //Yes we have a value here:
                    echo '<div>'.$er['name'].': '.object_link($er['object_code'], $e[$engagement_field], $e['e_b_id']).'</div>';
                } elseif(intval($e[$engagement_field])>0) {
                    echo '<div>'.$er['name'].': #'.$e[$engagement_field].'</div>';
                }
            }

            echo '</td>';
            echo '<td style="text-align:center !important;">'.( $e['e_has_blob']=='t' ? '<a href="/api_v1/ej_list/'.$e['e_id'].'" target="_blank" data-toggle="tooltip" title="Analyze Engagement JSON Blob in a new window" aria-hidden="true" data-placement="left"><i class="fa fa-search-plus" id="icon_'.$e['e_id'].'" aria-hidden="true"></i></a>' : '' ).'</td>';
            echo '</tr>';
        }
        ?>
        </tbody>
    </table>