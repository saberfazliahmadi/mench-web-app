
<script>
    //Define some global variables:
    var in_status_locked = <?= json_encode($this->config->item('in_status_locked')) ?>;
    var en_all_4486 = <?= json_encode($this->config->item('en_all_4486')) ?>;
    var en_all_4331 = <?= json_encode($this->config->item('en_all_4331')) ?>;
</script>
<script src="/js/custom/intent-manage-js.js?v=v<?= $this->config->item('app_version') ?>"
        type="text/javascript"></script>



<div id="modifybox" class="fixed-box hidden" intent-id="0" intent-tr-id="0" level="0">

    <h5 class="badge badge-h edit-header" style="display: inline-block;"><i class="fas fa-cog"></i> Modify</h5>
    <span id="hb_598" class="help_button bold-header" intent-id="598"></span>
    <div style="text-align:right; font-size: 22px; margin:-32px 3px -20px 0;">
        <a href="javascript:void(0)" onclick="$('#modifybox').addClass('hidden')"><i
                class="fas fa-times-circle"></i></a>
    </div>
    <div class="grey-box">

        <div class="loadbox hidden"><i class="fas fa-spinner fa-spin"></i> Loading...</div>

        <div class="row loadcontent">

            <div class="col-md-12">
                <div class="help_body" id="content_598"></div>
            </div>

            <div class="col-md-6 inlineform">


                <div class="title"><h4><i
                            class="fas fa-hashtag"></i> Intent Settings
                    </h4></div>


                <div class="inline-box" style="margin-bottom: 15px;">


                    <span class="mini-header">Intent Status:</span>
                    <select class="form-control border" id="in_status" original-status="" data-toggle="tooltip" title="Intent Status" data-placement="top" style="display: inline-block !important;">
                        <?php
                        foreach (echo_fixed_fields('in_status') as $status_id => $status) {
                            echo '<option value="' . $status_id . '" title="' . $status['s_desc'] . '">' . $status['s_name'] . '</option>';
                        }
                        ?>
                    </select> <i class="fas fa-lock in_status_lock hidden" data-toggle="tooltip" title="Intent status locked by system" data-placement="top"></i>
                    <span class="checkbox apply-recursive inline-block hidden">
                                <label style="display:inline-block !important; font-size: 0.9em !important; margin-left:5px;">
                                    <input type="checkbox" id="apply_recursively"/>
                                    <span class="underdot" data-toggle="tooltip" data-placement="top"
                                          title="If chcecked will also apply the new status recursively down (children, grandchildren, etc...) that have the same original status">Recursive
                                    </span>
                                </label>
                            </span>

                    <div class="notify_in_remove hidden">
                        <div class="alert alert-danger" style="margin:5px 0px; padding:7px;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Saving will remove intent and unlink all parents and children
                        </div>
                    </div>




                    <span class="mini-header" style="margin-top: 20px;">Outcome: [<span
                            style="margin:0 0 10px 0;"><span
                                id="charNameNum">0</span>/<?= $this->config->item('in_outcome_max') ?></span>][<a href="/entities/5008" data-toggle="tooltip" title="See (and manage) list of supporting verbs that intent outcomes can start with" data-placement="right" target="_blank"><b>Verbs</b></a>]</span>
                    <div class="form-group label-floating is-empty" style="height: 40px !important;">
                        <span class="white-wrapper"><textarea class="form-control text-edit msg main-box border" id="in_outcome" onkeyup="in_outcome_counter()"></textarea></span>
                    </div>



                    <span class="mini-header" style="margin-top: 20px;">Intent Type:</span>
                    <div class="form-group label-floating is-empty" style="margin-bottom: 0; padding-bottom: 0; display:block !important;">
                        <?php
                        foreach (echo_fixed_fields('in_type') as $in_val => $intent_type) {
                            echo '<span class="radio" style="display:inline-block; margin-top: 0 !important;" data-toggle="tooltip" title="' . $intent_type['s_desc'] . '" data-placement="top">
                                        <label class="underdot" style="display:inline-block;">
                                            <input type="radio" id="in_type_' . $in_val . '" name="in_type" value="' . $in_val . '" />
                                            ' . $intent_type['s_icon'] . ' ' . $intent_type['s_name'] . '
                                        </label>
                                    </span>';
                        }
                        ?>
                    </div>



                    <div class="<?= echo_advance() ?>">

                        <span class="mini-header">Completion Method:</span>
                        <select class="form-control border" id="in_requirement_entity_id" data-toggle="tooltip" title="Defines what students need to do to mark this intent as complete" data-placement="top" style="margin-bottom: 12px;">
                            <?php
                            foreach ($this->config->item('en_all_4331') as $en_id => $m) {
                                echo '<option value="' . $en_id . '">' . $m['m_name'] . ' Required</option>';
                            }
                            ?>
                        </select>

                    </div>



                    <span class="mini-header">Completion Cost:</span>
                    <div class="form-group label-floating is-empty">
                        <div class="input-group border" style="width: 155px;">
                                        <span class="input-group-addon addon-lean addon-grey" style="color:#2f2739; font-weight: 300;"><i
                                                class="fal fa-clock"></i></span>
                            <input style="padding-left:3px;" type="number" step="1" min="0"
                                   max="<?= $this->config->item('in_seconds_cost_max') ?>" id="in_seconds_cost" value=""
                                   class="form-control">
                            <span class="input-group-addon addon-lean addon-grey" style="color:#2f2739; font-weight: 300;">Seconds</span>
                        </div>
                    </div>


                    <div class="form-group label-floating is-empty">
                        <div class="input-group border" style="margin-top:1px; width: 155px;">
                                        <span class="input-group-addon addon-lean addon-grey" style="color:#2f2739; font-weight: 300;"><i
                                                class="fal fa-usd-circle"></i></span>
                            <input style="padding-left:3px;" type="number" step="0.01" min="0" max="5000"
                                   id="in_dollar_cost" value="" class="form-control">
                            <span class="input-group-addon addon-lean addon-grey"
                                  style="color:#2f2739; font-weight: 300;">USD</span>
                        </div>
                    </div>



                </div>

            </div>

            <div class="col-md-6">

                <div class="title">
                    <h4>
                        <i class="fas fa-link rotate90"></i> Link Settings
                    </h4>
                </div>


                <div class="inline-box" style="margin-bottom:0px;">

                    <div class="in-no-tr hidden">
                        <p>Not applicable because you are viewing the intent itself.</p>
                    </div>

                    <div class="in-has-tr">


                        <div class="modify_parent_in hidden">
                            <span class="mini-header"><span class="tr_in_link_title"></span> Linked Intent:</span>
                            <input style="padding-left:3px;" type="text" class="form-control algolia_search border in_quick_search" id="tr_in_link_update" value="" placeholder="Search replacement intent..." />
                        </div>


                        <span class="mini-header">Link Type: <span class="<?= echo_advance() ?>">[<a href="javscript:void(0);" onclick="$('.modify_parent_in').toggleClass('hidden')" data-toggle="tooltip" title="Modify Linked Intent" data-placement="top"><u>EDIT</u></a>]</span></span>
                        <div class="form-group label-floating is-empty">

                            <?php
                            foreach ($this->config->item('en_all_4486') as $en_id => $m) {
                                echo '<span class="radio" style="display:inline-block; margin-top: 0 !important;" data-toggle="tooltip" title="' . $m['m_desc'] . '" data-placement="top">
                                            <label class="underdot">
                                                <input type="radio" id="ln_type_entity_id_' . $en_id . '" name="ln_type_entity_id" value="' . $en_id . '" />
                                                '.$m['m_icon'].' ' . $m['m_name'] . '
                                            </label>
                                        </span>';
                            }
                            ?>

                        </div>

                        <div class="score_range_box hidden">
                            <span class="mini-header">Routing Logic:</span>
                            <div class="form-group label-floating is-empty"
                                 style="max-width:230px; margin:1px 0 10px;" data-toggle="tooltip" title="Min/Max assessment marks scored between 0-100%" data-placement="top">
                                <div class="input-group border">
                                    <span class="input-group-addon addon-lean addon-grey" style="color:#2f2739; font-weight: 300;">IF Scores </span>
                                    <input style="padding-left:0; padding-right:0; text-align:right;" type="text"
                                           maxlength="3" id="tr__conditional_score_min" value="" class="form-control">
                                    <span class="input-group-addon addon-lean addon-grey" style="color:#2f2739; font-weight: 300; border-left: 1px solid #ccc;"><i
                                            class="fal fa-fas fa-percentage"></i> to </span>
                                    <input style="padding-left:3px; padding-right:0; text-align:right;" type="text"
                                           maxlength="3" id="tr__conditional_score_max" value="" class="form-control">
                                    <span class="input-group-addon addon-lean addon-grey" style="color:#2f2739; font-weight: 300; border-left: 1px solid #ccc; border-right:0px solid #FFF;"><i
                                            class="fal fa-fas fa-percentage"></i></span>
                                </div>
                            </div>
                        </div>

                        <div class="score_points hidden">
                            <span class="mini-header">Assessment Marks:</span>
                            <select class="form-control border" id="tr__assessment_points" data-toggle="tooltip" title="Marks awarded to students for providing the right answer to a question" data-placement="top" style="margin-bottom:12px;">
                                <?php
                                foreach ($this->config->item('in_mark_options') as $mark) {
                                    echo '<option value="' . $mark . '">' . abs($mark) . ' Mark' . echo__s(abs($mark)) . '</option>';
                                }
                                ?>
                            </select>
                        </div>


                        <span class="mini-header">Link Status:</span>
                        <select class="form-control border" data-toggle="tooltip" title="Link Status" data-placement="top" id="ln_status" style="display: inline-block !important;">
                            <?php
                            foreach (echo_fixed_fields('ln_status') as $status_id => $status) {
                                if($status_id < 3){ //No need to verify intent links!
                                    echo '<option value="' . $status_id . '" title="' . $status['s_desc'] . '">' . $status['s_name'] . '</option>';
                                }
                            }
                            ?>
                        </select>

                        <div class="notify_unlink_in hidden">
                            <div class="alert alert-warning" style="margin:5px 0px; padding:7px;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Saving will unlink intent
                            </div>
                        </div>

                    </div>

                </div>




                <div class="save-btn-spot">&nbsp;</div>

            </div>
        </div>

        <table class="save-btn-box loadcontent">
            <tr>
                <td class="save-result-td"><span class="save_intent_changes"></span></td>
                <td class="save-td"><a href="javascript:in_modify_save();" class="btn btn-primary">Save</a></td>
            </tr>
        </table>

    </div>

</div>


<div id="load_messaging_frame" class="fixed-box hidden">
    <h5 class="badge badge-h badge-h-max" id="tr_title"></h5>
    <div style="text-align:right; font-size: 22px; margin:-32px 3px -20px 0;">
        <a href="javascript:void(0)" onclick="$('#load_messaging_frame').addClass('hidden');"><i class="fas fa-times-circle"></i></a>
    </div>
    <div class="grey-box grey-box-messages" style="padding-bottom: 10px;">
        <iframe class="ajax-frame hidden" id="ajax_messaging_iframe" src=""></iframe>
        <span class="frame-loader hidden"><i class="fas fa-spinner fa-spin"></i> Loading Messages...</span></div>
</div>