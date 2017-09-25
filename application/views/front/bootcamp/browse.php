<h1>Online Bootcamps</h1>
<br />
<div class="row">
<?php 
foreach($bootcamps as $count=>$c){
    if(fmod($count,4)==0){
        echo '</div><div class="row">';
    }
    echo '<div class="col-sm-6 col-md-4">
			<div class="card card-product">
				<div class="card-image">
					<img class="img" src="'.$c['c_image_url'].'">
				</div>

				<div class="card-content">';
                //echo '<h6 class="category text-muted">'.$c['ct_icon'].' '.$c['ct_name'].'</h6>';
                echo '<h4 class="card-title">
						<a href="/bootcamps/'.$c['c_url_key'].'">'.echo_title($c['c_objective']).'</a>
					</h4>
					<div class="card-description">By ';
    
    //Print admins:
    foreach($c['c__cohorts'][0]['r__admins'] as $count2=>$admins){
        if($count2>0){
            echo ' & ';
        }
        echo '<img src="'.$admins['u_image_url'].'" /> '.$admins['u_fname'].' '.$admins['u_lname'];
    }
                     echo '</div>
					<div class="footer">
                        <div class="price">
							<h4>'.($c['c__cohorts'][0]['r_usd_price']>0?'$'.number_format($c['c__cohorts'][0]['r_usd_price'],0).' <span>USD</span>':'FREE').'</h4>
						</div>
                    	<div class="stats">
							Starts <b>'.time_format($c['c__cohorts'][0]['r_start_time'],1).'</b>
                    	</div>
                    </div>

				</div>

			</div>
		</div>';
}
?>
</div>