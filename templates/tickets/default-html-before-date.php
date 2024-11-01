<?php
if(!empty($location)){
    ?>
    <div id="content_location">
        <p>
            <label for="location"><?php echo __('Location', 'yith-event-tickets-for-woocommerce')?>: </label>
            <span id="location"><?php echo $location;?>
                            </span>
        </p>
    </div>
    <?php
}