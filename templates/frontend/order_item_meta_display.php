<?php
foreach ($meta_items as $item){
    ?>
        <dl class="variation">
            <dt class="variation-product_type"><?php echo $item['label'] ?> :</dt>
            <dd class="variation-product_type"><?php echo $item['value'] ?></dd>
        </dl>
    <?php
}