
<div class="help_body maxout below_h" id="content_6086"></div>

<ul class="nav nav-pills nav-pills-primary">
    <li class="<?= ( $object_name=='engagements' ? 'active' : '') ?>"><a href="/cockpit/browse/engagements"><i class="fas fa-exchange rotate45"></i> Engagements</a></li>
</ul>
<hr />

<?php
if($object_name=='none'){
    echo '<p>Select an item from the menu above to get started.</p>';
    echo '<p>p.s. curiosity killed the cat mate 😉​</p>';
} else {
    $this->load->view('console/cockpit/browse/'.$object_name.'_browse');
}
?>