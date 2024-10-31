<?php
if(isset($_GET['submit'])){
    $file_id = sanitize_text_field($_GET['file_id']);
    global $wpdb, $table_prefix;
    $wp_fp = $table_prefix.'fp_list';

    $q="Select * from $wp_fp where id = $file_id;";
$results = $wpdb->get_results($q);
foreach ($results as $row):
    $cont= $row->content;
    $xml_contents = $cont;
    ob_end_clean();
    header_remove();
    header('Content-type: text/xml');
    header('Content-Disposition: attachment; filename="'.$row->name.'.'.$row->type.'"');

    echo $xml_contents;
    die;
    endforeach;
}
if(isset($_GET['delete'])){
    $file_id = sanitize_text_field($_GET['file_id']);
    global $wpdb, $table_prefix;
    $wp_fp = $table_prefix.'fp_list';

    $q="DELETE FROM $wp_fp where id = $file_id;";
$results = $wpdb->get_results($q);   
 echo '<script>window.location.href = .'.admin_url('admin.php').'.?page=my-feed;</script>';
}
wp_head();
?>
<div class="container"><h3>Manage Feed</h3>
    <div><a href="<?php echo esc_url( admin_url( 'admin.php?page=new-feed' ) ); ?>"><button style="color:#000000;background: #bcb2ff;   border:white;">New Feed</button></a></div><br>
<table id="pager" class="wp-list-table widefat striped posts">
    <thead>
        <tr>
            <th>Feed Name</th>
        <th>Type</th>
        <th>Feed Url</th>
        <th>Action</th>
        <th>Last Updated</th>
        </tr>
    </thead>
    <tbody>
<?php  
global $wpdb, $table_prefix;
$wp_fp = $table_prefix.'fp_list';
$q="Select * from $wp_fp order by datetime desc;";
$results = $wpdb->get_results($q);
foreach ($results as $row):
    $r = $row->content;
ob_start()
?>
<tr>
<th><?php echo esc_html($row->name);?></th>
<th><?php echo esc_html($row->type);?></th>
<th id="text-wr">
<a href="<?php echo esc_url(plugins_url("googlefiles/$row->name.$row->type", __FILE__));?>" target="_blank"><?php echo esc_html(plugins_url("googlefiles/$row->name.$row->type", __FILE__));?></a></th>
<th>
    <form action="<?php echo esc_html(admin_url('admin.php')); ?>" >
    <input type="hidden" name="page" value="my-feed">
    <input type="hidden" id="file_id" name="file_id" value="<?php echo esc_html($row->id);?>">
    <button title="Download" class="btn btn-link" type="submit" name="submit"><i style="font-size:20px;" class="material-icons">&#xe2c0;</i></button>
<a href="javascript: delete_user(<?php echo esc_html($row->id);?>)" title="Delete Feed"><i style="font-size:20px;color:red;" class="material-icons">&#xe92b;</i></a>
</form>
    
    
</th>
<th><?php echo esc_html($row->datetime);?></th>
</tr>

<?php

echo ob_get_clean();
endforeach;?>
    </tbody>
</table>
<div id="pageNavPosition" class="pager-nav"></div>

<?php wp_footer();?>

