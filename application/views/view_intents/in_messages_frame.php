<?php

//Fetch Messages based on in_id:
$session_en = $this->session->userdata('user');
$ln_content_max_length = $this->config->item('ln_content_max_length');
$en_ids_4485 = $this->config->item('en_ids_4485');
$en_all_4485 = $this->config->item('en_all_4485');

//Fetch all messages:
$metadatas = $this->Links_model->ln_fetch(array(
    'ln_status >=' => 0, //New+
    'ln_type_entity_id IN (' . join(',', $en_ids_4485) . ')' => null, //All Intent Notes
    'ln_child_intent_id' => $in_id,
), array(), 0, 0, array('ln_order' => 'ASC'));


//To be populated:
$counters = array();
$metadata_body_ui = '';
foreach ($metadatas as $ln) {

    $metadata_body_ui .= echo_in_message_manage(array_merge($ln, array(
        'ln_child_entity_id' => $session_en['en_id'],
    )));

    //Increase counter:
    if (isset($counters[$ln['ln_type_entity_id']])) {
        $counters[$ln['ln_type_entity_id']]++;
    } else {
        $counters[$ln['ln_type_entity_id']] = 1;
    }

}

?>


<script>
    //pass core variables to JS:
    var in_id = <?= $in_id ?>;
    var ln_content_max_length = <?= $ln_content_max_length ?>;
    var metadata_count = <?= count($metadatas) ?>;
    var focus_ln_type_entity_id = <?= $en_ids_4485[0] ?>; //The message type that is the focus on-start.
</script>
<script src="/js/custom/intent-messaging-js.js?v=v<?= $this->config->item('app_version') ?>" type="text/javascript"></script>



<!-- Message types navigation menu -->
<ul class="nav nav-tabs iphone-nav-tabs">
    <?php
    foreach ($en_all_4485 as $ln_type_entity_id => $m) {
        echo '<li role="presentation" class="nav_' . $ln_type_entity_id . ' active '.( in_array(5007 , $m['m_parents']) ? ' ' . echo_advance() . '' : '' ).'">';
        echo '<a href="#intentnotes-' . $in_id . '-'.$ln_type_entity_id.'"> ' . $m['m_icon'] . ' ' . $m['m_name'] . 's [<span class="mtd_count_'.$in_id.'_'.$ln_type_entity_id.'">'.( isset($counters[$ln_type_entity_id]) ? $counters[$ln_type_entity_id] : 0 ).'</span>] </a>';
        echo '</li>';
    }
    ?>
</ul>


<div id="intent_messages<?= $in_id ?>">

    <?php

    //Show no-Message notifications for each message type:
    foreach ($en_all_4485 as $ln_type_entity_id => $m) {


        echo '<div class="all_msg msg_en_type_' . $ln_type_entity_id . ' sorting-enabled">';


        //Learn more option:
        echo '<i class="fal fa-info-circle"></i> <span data-toggle="tooltip" title="'.$m['m_desc'].'" data-placement="bottom" class="underdot">Usage Notes</span> &nbsp;';


        //Does it support sorting?
        if(in_array(4603, $en_all_4485[$ln_type_entity_id]['m_parents'])){
            echo '<span class="' . echo_advance() . '"><i class="fas fa-exchange rotate90"></i> <span data-toggle="tooltip" class="underdot" title="Messages are delivered in order so you can can sort them as needed" data-placement="bottom">Sortable</span> &nbsp;</span>';
        }

        //Does it support entity referencing?
        if(in_array(4986, $en_all_4485[$ln_type_entity_id]['m_parents'])){
            echo '<span class="' . echo_advance() . '"><i class="fas fa-at"></i> <span data-toggle="tooltip" class="underdot" title="You can reference up to 1 entity using the @ sign" data-placement="bottom">Entity Reference</span> &nbsp;</span>';
        }

        //Does it require intent voting?
        if(in_array(4985, $en_all_4485[$ln_type_entity_id]['m_parents'])){
            echo '<span class="' . echo_advance() . '"><i class="fas fa-hashtag"></i> <span data-toggle="tooltip" class="underdot" title="You can reference up to 1 parent intent using the # sign" data-placement="bottom">Intent Reference</span> &nbsp;</span>';
        }

        echo '</div>';


        if (!isset($counters[$ln_type_entity_id])) {
            echo '<div class="ix-tip no-messages' . $in_id . '_' . $ln_type_entity_id . ' all_msg msg_en_type_' . $ln_type_entity_id . '"><i class="fas fa-exclamation-triangle"></i> No ' . strtolower($m['m_name']) . ' added yet</div>';
        }
    }

    //Count each message type:
    echo '<div id="message-sorting" class="list-group list-messages">';
    echo $metadata_body_ui;
    echo '</div>';

    ?>
</div>

<div style="margin-top:-7px;">
    <?php
    echo '<div class="list-group list-messages">';
    echo '<div class="list-group-item">';

    echo '<div class="add-msg add-msg' . $in_id . '">';
    echo '<form class="box box' . $in_id . '" method="post" enctype="multipart/form-data">'; //Used for dropping files

    echo '<textarea onkeyup="count_message()" class="form-control msg msgin algolia_search" style="min-height:80px; box-shadow: none; resize: none; margin-bottom: 0px;" id="ln_content' . $in_id . '" placeholder="Write Message, Drop a File or Paste URL"></textarea>';

    echo '<div id="ln_content_counter" style="margin:0 0 1px 0; font-size:0.8em;">';
    //File counter:
    echo '<span id="charNum' . $in_id . '">0</span>/' . $ln_content_max_length;

    ///firstname
    echo '<a href="javascript:add_first_name();" class="textarea_buttons ' . echo_advance() . ' remove_loading" style="float:right; margin-left:8px;" data-toggle="tooltip" title="Replaced with master\'s First Name for a more personal message." data-placement="left"><i class="fas fa-fingerprint"></i> /firstname</a>';

    //Choose a file:
    echo '<div style="float:right; display:inline-block;" class="remove_loading"><input class="box__file inputfile" type="file" name="file" id="file" /><label class="textarea_buttons" for="file" data-toggle="tooltip" title="Upload files up to ' . $this->config->item('max_file_mb_size') . ' MB" data-placement="top"><i class="fal fa-cloud-upload"></i> Upload</label></div>';
    echo '</div>';


    //Fetch for all message types:
    foreach ($en_all_4485 as $ln_type_entity_id => $m) {
        echo '<div class="iphone-add-btn all_msg msg_en_type_' . $ln_type_entity_id . '"><a href="javascript:message_create();" id="add_message_' . $ln_type_entity_id . '_' . $in_id . '" data-toggle="tooltip" title="or hit CTRL+ENTER ;)" data-placement="right" class="btn btn-primary">ADD '.$m['m_icon'].' ' . $m['m_name'] . '</a></div>';
    }

    echo '</form>';
    echo '</div>';

    echo '</div>';
    echo '</div>';
    ?>
</div>
