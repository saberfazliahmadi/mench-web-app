<?php
$message_max = $this->config->item('message_max');
$udata = $this->session->userdata('user');
?>

<style>
    /* Access Permissions */
    .is-passwordable{display:<?= ( in_array($entity['u_inbound_u_id'], array(1280,1279,1307,1281,1308,1304)) ? 'inline-block' : 'none') ?>;}
    .needs-current-pass{display:<?= ( strlen($entity['u_password'])>0 && !($udata['u_inbound_u_id']==1281) ? 'block' : 'none' ) ?>;}
    .is-people{display:<?= (in_array($entity['u_inbound_u_id'],array(1280,1279,1307,1281,1308,1304,1282)) ? 'block' : 'none') ?>;}
    .is-mediator{display:<?= (in_array($entity['u_inbound_u_id'],array(1308,1280,1281)) ? 'block' : 'none') ?>;}
    .is-login-mediator{display:<?= ( isset($udata['u_inbound_u_id']) && $udata['u_inbound_u_id']==1281 ? 'block' : 'none') ?>;}
</style>




<script>

$(document).ready(function() {
	//Detect any possible hashes that controll the menu?
	if(window.location.hash) {
		focus_hash(window.location.hash);
    }

	//Counter:
	changeBio();



	//Lookout for primary intent searches:
    $( "#add_authors_input" ).on('autocomplete:selected', function(event, suggestion, dataset) {

        add_u_link(suggestion.u_id, null, 'inbound');

    }).autocomplete({ hint: false, keyboardShortcuts: ['a'] }, [{

        source: function(q, cb) {
            algolia_u_index.search(q, {
                hitsPerPage: 7,
                filters:'(u_inbound_u_id=1280 OR u_inbound_u_id=1279 OR u_inbound_u_id=1307 OR u_inbound_u_id=1281 OR u_inbound_u_id=1308 OR u_inbound_u_id=1304 OR u_inbound_u_id=1282)',
            }, function(error, content) {
                if (error) {
                    cb([]);
                    return;
                }
                cb(content.hits, content);
            });
        },
        displayKey: function(suggestion) { return "" },
        templates: {
            suggestion: function(suggestion) {
                //If clicked, would trigger the autocomplete:selected above which will trigger the add_u_link() function
                return '<span><i class="fas fa-at"></i></span> '+ suggestion.u_full_name + ' ('+suggestion.u_inbound_name+')';
            },
            header: function(data) {
                if(!data.isEmpty){
                    return '<a href="javascript:add_u_link(0,\''+data.query+'\',\'inbound\')" class="suggestion"><span><i class="fas fa-plus-circle"></i> Create</span> "'+data.query+'"'+' (Referenced Auhtors)</a>';
                }
            },
            empty: function(data) {
                return '<a href="javascript:add_u_link(0,\''+data.query+'\',\'inbound\')" class="suggestion"><span><i class="fas fa-plus-circle"></i> Create</span> "'+data.query+'"'+' (Referenced Auhtors)</a>';
            },
        }
    }]).keypress(function (e) {
        var code = (e.keyCode ? e.keyCode : e.which);
        if ((code == 13) || (e.ctrlKey && code == 13)) {
            add_u_link(0, $("#add_authors_input").val(), 'inbound');
            return true;
        }
    });
});

//Count text area characters:
function changeBio() {
    var len = $('#u_bio').val().length;
    if (len > <?= $message_max ?>) {
    	$('#charNum').addClass('overload').text(len);
    } else {
        $('#charNum').removeClass('overload').text(len);
    }
}



function update_account(){

    //Show spinner:
    $('.update_u_results').html('<span><img src="/img/round_load.gif" class="loader" /></span>').hide().fadeIn();

    $.post("/entities/entity_save_edit", {

        u_id:<?= $entity['u_id'] ?>,
        u_full_name:$('#u_full_name').val(),
        u_email:$('#u_email').val(),
        u_phone:$('#u_phone').val(),
        u_gender:$('#u_gender').val(),
        u_country_code:$('#u_country_code').val(),
        u_current_city:$('#u_current_city').val(),
        u_timezone:$('#u_timezone').val(),
        u_language:$('#u_language').val(),
        u_paypal_email:$('#u_paypal_email').val(),
        u_newly_checked:(document.getElementById('u_terms_agreement_time').checked ? '1' : '0'),

        u_bio:$('#u_bio').val(),

        u_password_current:$('#u_password_current').val(),
        u_password_new:$('#u_password_new').val(),

    } , function(data) {
        //Update UI to confirm with user:
        $('.update_u_results').html(data).hide().fadeIn();

        //Disapper in a while:
        setTimeout(function() {
            $('.update_u_results').fadeOut();
        }, 10000);
    });
}

function u_delete(u_id, u_redirect_id){

    var r = confirm("Are you sure you want to PERMANENTLY delete this entity?");
    if (r == true) {
        //Show spinner:
        $('.u_delete').html('<span><img src="/img/round_load.gif" class="loader" /></span>').hide().fadeIn();

        $.post("/entities/delete/"+u_id, {} , function(data) {

            if(data.status){

                $('.u_delete').html('<span>Success, Redirecting now...</span>');

                //Redirect to parent entity
                setTimeout(function() {
                    window.location = "/entities/"+u_redirect_id;
                }, 2584);

            } else {
                //We had some error:
                $('.u_delete').html('<span style="color:#FF0000;">Error: '+data.message+'</span>');
            }

        });
    }
}

function insert_gravatar(){
	var gravatar_url = 'https://www.gravatar.com/avatar/<?= md5(trim(strtolower($entity['u_email']))) ?>?d=404';
	$('.profile-pic').attr('src',gravatar_url);
    //TODO Insert gravatar_url into URL Adding Box
    alert('Gravatar URL for your email <?= $entity['u_email'] ?> was successfully inserted. Make sure to SAVE changes.');
}
</script>





<ul id="topnav" class="nav nav-pills nav-pills-primary">
  <li id="nav_profile" class="active"><a href="#profile"><i class="fas fa-user-circle"></i> Profile</a></li>
  <li id="nav_password" class="is-passwordable"><a href="#password"><i class="fas fa-lock"></i> Password</a></li>
</ul>




<div class="tab-content tab-space">

    <div class="tab-pane active" id="tabprofile">


        <div class="title" style="margin-bottom:0; padding-bottom:0;"><h4><i class="fas fa-id-card"></i> Full Name</h4></div>
        <input type="text" required id="u_full_name" value="<?= $entity['u_full_name'] ?>" data-lpignore="true" placeholder="Full Name" class="form-control border">





        <div class="title" style="margin-top:20px;"><h4><i class="fas fa-comment-dots"></i> Summary</h4></div>
        <textarea class="form-control text-edit border msg" id="u_bio" style="height:100px;" onkeyup="changeBio()"><?= substr(trim(strip_tags($entity['u_bio'])),0,$message_max); ?></textarea>
        <div style="margin:0 0 10px 0; font-size:0.8em;"><span id="charNum">0</span>/<?= $message_max ?></div>




        <div class="is-login-mediator hidden">
            <div class="title" style="margin-top:20px;"><h4><i class="fas fa-hashtag"></i> Primary Intent <span id="hb_6980" class="help_button" intent-id="6980"></span></h4></div>
            <div class="help_body maxout" id="content_6980"></div>


            <div class="input-group border">
                <span class="input-group-addon addon-lean"><i class="fas fa-hashtag" style="color:#3C4858;"></i></span>
                <input style="padding-left:3px;" type="text" id="u_outbound_c_id" value="<?= $entity['u_outbound_c_id'] ?>" class="form-control c_outcome_input">
            </div>
        </div>



        <div class="is-people">
            <div class="title" style="margin-top:20px;"><h4><i class="fas fa-envelope"></i> Primary Email <i class="fas fa-eye-slash" data-toggle="tooltip" title="Will NOT be published publicly"></i></h4></div>
            <input type="email" id="u_email" data-lpignore="true" style="max-width:260px;" value="<?= $entity['u_email'] ?>" class="form-control border">
        </div>



        <div class="is-people">
            <div class="title" style="margin-top:20px;"><h4><i class="fas fa-language"></i> Languages</h4></div>
            <p>Hold down Ctrl to select multiple:</p>
            <div class="form-group label-floating is-empty">
                <select multiple id="u_language" style="height:150px;" class="border">
                    <?php
                    $all_languages = $this->config->item('languages');
                    $my_languages = explode(',',$entity['u_language']);
                    foreach($all_languages as $ln_key=>$ln_name){
                        echo '<option value="'.$ln_key.'" '.(in_array($ln_key,$my_languages)?'selected="selected"':'').'>'.$ln_name.'</option>';
                    }
                    ?>
                </select>
                <span class="material-input"></span>
            </div>
        </div>



        <div class="is-people">
            <div class="title" style="margin-top:20px;"><h4><i class="fas fa-map"></i> Timezone</h4></div>
            <div class="form-group label-floating is-empty">
                <select id="u_timezone" class="border">
                    <option value="">Choose...</option>
                    <?php
                    $timezones = $this->config->item('timezones');
                    foreach($timezones as $tz_val=>$tz_name){
                        echo '<option value="'.$tz_val.'" '.($entity['u_timezone']==$tz_val?'selected="selected"':'').'>'.$tz_name.'</option>';
                    }
                    ?>
                </select>
                <span class="material-input"></span>
            </div>
        </div>




        <div class="is-people">
            <div class="title" style="margin-top:20px;"><h4><i class="fas fa-phone-square"></i> Phone <i class="fas fa-eye-slash" data-toggle="tooltip" title="Will NOT be published publicly"></i></h4></div>
            <div class="form-group label-floating is-empty">
                <input type="tel" maxlength="30" required id="u_phone" data-lpignore="true" style="max-width:260px;" value="<?= $entity['u_phone'] ?>" class="form-control border">
                <span class="material-input"></span>
            </div>
        </div>



        <div class="is-people">
            <div class="title" style="margin-top:20px;"><h4><i class="fas fa-map-marker"></i> Location</h4></div>
            <div class="form-group label-floating is-empty">
                <select id="u_country_code" class="border" style="width:100%; margin-bottom:10px; max-width:260px;">
                    <option value="">Choose...</option>
                    <?php
                    $countries_all = $this->config->item('countries_all');
                    foreach($countries_all as $country_key=>$country_name){
                        echo '<option value="'.$country_key.'" '.($entity['u_country_code']==$country_key?'selected="selected"':'').'>'.$country_name.'</option>';
                    }
                    ?>
                </select>
                <span class="material-input"></span>
            </div>
            <input type="text" required id="u_current_city" placeholder="Vancouver" style="max-width:260px;" data-lpignore="true" value="<?= $entity['u_current_city'] ?>" class="form-control border">
        </div>





        <div class="is-people">
            <div class="title" style="margin-top:20px;"><h4><i class="fas fa-venus-mars"></i> Gender</h4></div>
            <div class="form-group label-floating is-empty">
                <select id="u_gender" class="border">
                    <option value="">Neither</option>
                    <?php
                    echo '<option value="m" '.($entity['u_gender']=='m'?'selected="selected"':'').'>Male</option>';
                    echo '<option value="f" '.($entity['u_gender']=='f'?'selected="selected"':'').'>Female</option>';
                    ?>
                </select>
                <span class="material-input"></span>
            </div>
        </div>


        <div class="is-mediator">
            <div class="title" style="margin-top:15px;"><h4><i class="fab fa-paypal"></i> Paypal Payout Email</h4></div>
            <div class="form-group label-floating is-empty">
                <input type="email" id="u_paypal_email" data-lpignore="true" style="max-width:260px;" value="<?= $entity['u_paypal_email'] ?>" class="form-control border">
                <span class="material-input"></span>
            </div>
        </div>



        <div class="is-mediator">
            <div>
                <div class="title" style="margin-top:20px;"><h4><i class="fas fa-badge-check"></i> Coach Agreement</h4></div>
                <ul>
                    <li>I have read and understood how <a href="https://support.mench.com/hc/en-us/articles/115002473111" target="_blank"><u>Coach Earning & Payouts <i class="fas fa-external-link-square" style="font-size: 0.8em;"></i></u></a> work.</li>
                    <li>I have read and understood the <a href="https://support.mench.com/hc/en-us/articles/115002096752" target="_blank"><u>Mench Code of Conduct <i class="fas fa-external-link-square" style="font-size: 0.8em;"></i></u></a>.</li>
                    <li>I have read and understood the <a href="https://support.mench.com/hc/en-us/articles/115002096732" target="_blank"><u>Mench Honor Code <i class="fas fa-external-link-square" style="font-size: 0.8em;"></i></u></a>.</li>
                    <li>I have read and agreed to Mench's <a href="/terms" target="_blank"><u>Terms of Service & Privacy Policy <i class="fas fa-external-link-square" style="font-size: 0.8em;"></i></u></a>.</li>
                </ul>
                <div class="form-group label-floating is-empty">
                    <div class="checkbox">
                        <label>
                            <?php $has_agreed = (isset($entity['u_terms_agreement_time']) && strlen($entity['u_terms_agreement_time'])>0); ?>
                            <?php if($has_agreed){ ?>
                                <input type="checkbox" id="u_terms_agreement_time" disabled checked /> Agreed on <b><?= echo_time($entity['u_terms_agreement_time'],0) ?> PST</b>
                            <?php } else { ?>
                                <input type="checkbox" id="u_terms_agreement_time" <?= ( $udata['u_id']==$entity['u_id'] ? '' : 'disabled') ?> /> I certify that all above statements are true <?= ( $udata['u_id']==$entity['u_id'] ? '' : '<i class="fas fa-lock" data-toggle="tooltip" data-placement="left" title="Only owner can mark this as done​"></i>') ?>
                            <?php } ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>




        <table width="100%" style="margin-top:30px;"><tr><td class="save-td"><a href="javascript:update_account();" class="btn btn-primary">Save</a></td><td><span class="update_u_results"></span></td></tr></table>

        <?php
        if($udata['u_inbound_u_id']==1281){
            echo '<div class="u_delete" style="text-align: right;"><a href="javascript:void(0);" onclick="u_delete('.$entity['u_id'].','.$entity['u_inbound_u_id'].')">Delete Entity</a></div>';
        }
        ?>

    </div>





    <div class="tab-pane is-passwordable" id="tabpassword">

        <div class="needs-current-pass">
            <div class="title"><h4><i class="fas fa-asterisk"></i> Current Password</h4></div>
            <div class="form-group label-floating is-empty">
                <input type="password" id="u_password_current" style="max-width: 260px;" class="form-control border">
                <span class="material-input"></span>
            </div>
        </div>

        <div class="title" style="margin-top:30px;"><h4><i class="fas fa-asterisk"></i> Set New Password</h4></div>
        <div class="form-group label-floating is-empty">
            <input type="password" id="u_password_new" style="max-width: 260px;" autocomplete="off" class="form-control border">
            <span class="material-input"></span>
        </div>

        <table width="100%" style="margin-top:30px;"><tr><td class="save-td"><a href="javascript:update_account();" class="btn btn-primary">Save</a></td><td><span class="update_u_results"></span></td></tr></table>
    </div>

</div>