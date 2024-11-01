<?php
if(!empty($message_start) & !empty($message_end)){
    ?>
    <div class="date_panel">
        <span><?php echo $message_start ?></span>
        <span><?php echo $message_end ?></span>
    </div>
<?php }

do_action('yith_wcevti_before_date_panel');
?>